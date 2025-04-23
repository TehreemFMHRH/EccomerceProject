<?php

namespace Webkul\Shop\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\BookingProduct\Helpers\AppointmentSlot as AppointmentSlotHelper;
use Webkul\BookingProduct\Helpers\DefaultSlot as DefaultSlotHelper;
use Webkul\BookingProduct\Helpers\EventTicket as EventTicketHelper;
use Webkul\BookingProduct\Helpers\RentalSlot as RentalSlotHelper;
use Webkul\BookingProduct\Helpers\TableSlot as TableSlotHelper;
use Webkul\BookingProduct\Models\BookingProduct;
use Illuminate\Support\Facades\Request;
use Webkul\BookingProduct\Repositories\BookingProductDefaultSlotRepository;
use Webkul\BookingProduct\Repositories\BookingProductAppointmentSlotRepository;
use Webkul\BookingProduct\Repositories\BookingProductEventTicketRepository;
use Webkul\BookingProduct\Repositories\BookingProductRentalSlotRepository;
use Webkul\BookingProduct\Repositories\BookingProductTableSlotRepository;

class BookingProductController extends Controller
{
    protected array $bookingHelpers = [];
    protected $typeRepositories = [];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected DefaultSlotHelper $defaultSlotHelper,
        protected AppointmentSlotHelper $appointmentSlotHelper,
        protected RentalSlotHelper $rentalSlotHelper,
        protected EventTicketHelper $eventTicketHelper,
        protected TableSlotHelper $tableSlotHelper,
        protected BookingProductDefaultSlotRepository $bookingProductDefaultSlotRepository,
        protected BookingProductAppointmentSlotRepository $bookingProductAppointmentSlotRepository,
        protected BookingProductEventTicketRepository $bookingProductEventTicketRepository,
        protected BookingProductRentalSlotRepository $bookingProductRentalSlotRepository,
        protected BookingProductTableSlotRepository $bookingProductTableSlotRepository,
    ) {
        $this->bookingHelpers = [
            'default'     => $this->defaultSlotHelper,
            'appointment' => $this->appointmentSlotHelper,
            'rental'      => $this->rentalSlotHelper,
            'event'       => $this->eventTicketHelper,
            'table'       => $this->tableSlotHelper,
        ];
        $this->typeRepositories = [
            'default'     => $bookingProductDefaultSlotRepository,
            'appointment' => $bookingProductAppointmentSlotRepository,
            'event'       => $bookingProductEventTicketRepository,
            'rental'      => $bookingProductRentalSlotRepository,
            'table'       => $bookingProductTableSlotRepository,
        ];
    }

    /**
     * Get available slots for the given product and the date.
     */
    public function index(int $id): JsonResource
    {
        $bookingProduct = BookingProduct::find($id);

        return new JsonResource([
            'data' => $this->bookingHelpers[$bookingProduct->type]->getSlotsByDate($bookingProduct, request()->date),
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->all();

        // Direct logic for creating the BookingProduct without repository
        if (isset($data['slots'])) {
            $data['slots'] = $this->validateSlots($data);
        }

        $bookingProduct = BookingProduct::create($data);

        // Event handling logic directly in the controller
        if ($bookingProduct->type == 'event') {
            // Directly handle event ticket logic
            $this->saveEventTickets($data, $bookingProduct);
        } else {
            // No repository, just create directly
            $this->createSlot($data, $bookingProduct);
        }

        return response()->json($bookingProduct);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();

        if (isset($data['slots'])) {
            $data['slots'] = $this->skipOverlappingSlots($data['slots']);
        }

        $bookingProduct = BookingProduct::findOrFail($id);
        $bookingProduct->update($data);

        // Deleting other slots types
        foreach ($this->typeRepositories as $type => $repository) {
            if ($type == $data['type']) {
                continue;
            }

            $repository->deleteWhere(['booking_product_id' => $id]);
        }

        if ($bookingProduct->type == 'event') {
            $this->saveEventTickets($data, $bookingProduct);
        } else {
            // Direct slot management logic
            $this->createSlot($data, $bookingProduct);
        }
    }

    public function validateSlots(array $data): array
    {
        // Move the logic from the repository into the controller directly
        if (!isset($data['same_slot_all_days'])) {
            return $data['slots'];
        }

        if (!$data['same_slot_all_days']) {
            foreach ($data['slots'] as $day => $slots) {
                $data['slots'][$day] = $this->skipOverlappingSlots($slots);
            }
        } else {
            $data['slots'] = $this->skipOverlappingSlots($data['slots']);
        }

        return $data['slots'];
    }

    public function skipOverlappingSlots(array $slots): array
    {
        // Keep this logic directly in the controller
        $filteredSlots = [];

        foreach ($slots as $key => $slot) {
            if (isset($slot[0]) && is_array($slot[0])) {
                $filteredSlots[$key] = $this->processSlots($slot);
            } else {
                $filteredSlots = array_merge($filteredSlots, $this->processSlots([$slot]));
            }
        }

        return $filteredSlots;
    }

    public function processSlots(array $slots): array
    {
        $tempSlots = [];
        $validSlots = [];

        foreach ($slots as $key => $timeInterval) {
            $from = Carbon::createFromTimeString($timeInterval['from'])->getTimestamp();
            $to = Carbon::createFromTimeString($timeInterval['to'])->getTimestamp();

            if ($from > $to) {
                continue;
            }

            $isOverLapping = false;

            foreach ($tempSlots as $slot) {
                if (($slot['from'] <= $from && $slot['to'] >= $from) || ($slot['from'] <= $to && $slot['to'] >= $to)) {
                    $isOverLapping = true;
                    break;
                }
            }

            if (!$isOverLapping) {
                $tempSlots[] = ['from' => $from, 'to' => $to];
                $validSlots[] = $timeInterval;
            }
        }

        return $validSlots;
    }

    public function addSlots(array $data): array
    {
        if (isset($data['same_slot_all_days']) && !$data['same_slot_all_days']) {
            return [[], [], [], [], [], [], []];
        } else {
            return ($data['type'] == 'default' && $data['booking_type'] == 'many') ? [[], [], [], [], [], [], []] : [];
        }
    }

    /**
     * Format Slots data.
     */
    public function formatSlots(array $data): array
    {
        if (
            isset($data['same_slot_all_days'])
            && ! $data['same_slot_all_days']
        ) {
            for ($i = 0; $i < 7; $i++) {
                if (! isset($data['slots'][$i])) {
                    $data['slots'][$i] = [];
                } else {
                    $count = 0;

                    $slots = [];

                    foreach ($data['slots'][$i] as $slot) {
                        $slots[] = array_merge($slot, ['id' => $i.'_slot_'.$count]);

                        $count++;
                    }

                    $data['slots'][$i] = $slots;
                }
            }

            ksort($data['slots']);
        }

        return $data['slots'];
    }



}
