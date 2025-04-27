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


    public function index(int $i): JsonResource
    {
        $bookingProduct = BookingProduct::find($i);

        return new JsonResource([
            'data' => $this->bookingHelpers[$bookingProduct->type]->getSlotsByDate($bookingProduct, request()->date),
        ]);
    }

    public function create(Request $request)
    {
        $dat = $request->all();

        // Direct logic for creating the BookingProduct without repository
        if (isset($dat['slots'])) {
            $dat['slots'] = $this->validateSlots($dat);
        }

        $bookingProduct = BookingProduct::create($dat);

        // Event handling logic directly in the controller
        if ($bookingProduct->type == 'event') {
            // Directly handle event ticket logic
            $this->saveEventTickets($dat, $bookingProduct);
        } else {
            // No repository, just create directly
            $this->createSlot($dat, $bookingProduct);
        }

        return response()->json($bookingProduct);
    }

    public function update(Request $request, $i)
    {
        $dat = $request->all();

        if (isset($dat['slots'])) {
            $dat['slots'] = $this->skipOverlappingSlots($dat['slots']);
        }

        $bookingProduct = BookingProduct::findOrFail($i);
        $bookingProduct->update($dat);

        // Deleting other slots types
        foreach ($this->typeRepositories as $type => $repository) {
            if ($type == $dat['type']) {
                continue;
            }

            $repository->deleteWhere(['booking_product_id' => $i]);
        }

        if ($bookingProduct->type == 'event') {
            $this->saveEventTickets($dat, $bookingProduct);
        } else {
            // Direct slot management logic
            $this->createSlot($dat, $bookingProduct);
        }
    }

    public function validateSlots(array $dat): array
    {
        // Move the logic from the repository into the controller directly
        if (!isset($dat['same_slot_all_days'])) {
            return $dat['slots'];
        }

        if (!$dat['same_slot_all_days']) {
            foreach ($dat['slots'] as $day => $slots) {
                $dat['slots'][$day] = $this->skipOverlappingSlots($slots);
            }
        } else {
            $dat['slots'] = $this->skipOverlappingSlots($dat['slots']);
        }

        return $dat['slots'];
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

    public function addSlots(array $dat): array
    {
        if (isset($dat['same_slot_all_days']) && !$dat['same_slot_all_days']) {
            return [[], [], [], [], [], [], []];
        } else {
            return ($dat['type'] == 'default' && $dat['booking_type'] == 'many') ? [[], [], [], [], [], [], []] : [];
        }
    }


    public function formatSlots(array $dat): array
    {
        if (
            isset($dat['same_slot_all_days'])
            && ! $dat['same_slot_all_days']
        ) {
            for ($i = 0; $i < 7; $i++) {
                if (! isset($dat['slots'][$i])) {
                    $dat['slots'][$i] = [];
                } else {
                    $count = 0;

                    $slots = [];

                    foreach ($dat['slots'][$i] as $slot) {
                        $slots[] = array_merge($slot, ['id' => $i.'_slot_'.$count]);

                        $count++;
                    }

                    $dat['slots'][$i] = $slots;
                }
            }

            ksort($dat['slots']);
        }

        return $dat['slots'];
    }



}
