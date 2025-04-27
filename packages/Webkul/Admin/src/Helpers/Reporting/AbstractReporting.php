<?php

namespace Webkul\Admin\Helpers\Reporting;

use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

abstract class AbstractReporting
{
    
    protected array $channelIds;

    
    protected Carbon $startDate;

    
    protected Carbon $endDate;

    
    protected Carbon $lastStartDate;

    
    protected Carbon $lastEndDate;

    
    public function __construct()
    {
        $this->setChannel(request()->query('channel'));

        $this->setStartDate(request()->date('start'));

        $this->setEndDate(request()->date('end'));
    }

    
    public function setChannel(?string $code = null): self
    {
        $this->channelIds = core()->getAllChannels()
            ->filter(function ($channel) use ($code) {
                return $code ? $channel->code == $code : true;
            })
            ->pluck('id')
            ->toArray();

        // $this->channelIds = [2];

        return $this;
    }

    
    public function setStartDate(?Carbon $startDate = null): self
    {
        $this->startDate = $startDate ? $startDate->startOfDay() : now()->subDays(30)->startOfDay();

        $this->setLastStartDate();

        return $this;
    }

    
    public function setEndDate(?Carbon $endDate = null): self
    {
        $this->endDate = ($endDate && $endDate->endOfDay() <= now()) ? $endDate->endOfDay() : now();

        $this->setLastEndDate();

        return $this;
    }

    
    public function getStartDate(): Carbon
    {
        return $this->startDate;
    }

    
    public function getEndDate(): Carbon
    {
        return $this->endDate;
    }

    
    private function setLastStartDate(): void
    {
        if (! isset($this->startDate)) {
            $this->setStartDate(request()->date('start'));
        }

        if (! isset($this->endDate)) {
            $this->setEndDate(request()->date('end'));
        }

        $this->lastStartDate = $this->startDate->clone()->subDays($this->startDate->diffInDays($this->endDate));
    }

    
    private function setLastEndDate(): void
    {
        $this->lastEndDate = $this->startDate->clone();
    }

    
    public function getLastStartDate(): Carbon
    {
        return $this->lastStartDate;
    }

    
    public function getLastEndDate(): Carbon
    {
        return $this->lastEndDate;
    }

    
    public function getPercentageChange($previous, $current): float|int
    {
        if (! $previous) {
            return $current ? 100 : 0;
        }

        return ($current - $previous) / $previous * 100;
    }

    
    public function getTimeInterval($startDate, $endDate, $period)
    {
        if ($period == 'auto') {
            $totalMonths = $startDate->diffInMonths($endDate) + 1;

            
            $intervals = $this->getMonthsInterval($startDate, $endDate);

            if (! empty($intervals)) {
                return [
                    'group_column' => 'MONTH(created_at)',
                    'intervals'    => $intervals,
                ];
            }

            
            $intervals = $this->getWeeksInterval($startDate, $endDate);

            if (! empty($intervals)) {
                return [
                    'group_column' => 'WEEK(created_at)',
                    'intervals'    => $intervals,
                ];
            }

            
            return [
                'group_column' => 'DAYOFYEAR(created_at)',
                'intervals'    => $this->getDaysInterval($startDate, $endDate),
            ];
        } else {
            $datePeriod = CarbonPeriod::create($this->startDate, "1 $period", $this->endDate);

            if ($period == 'year') {
                $formatter = '?';
            } elseif ($period == 'month') {
                $formatter = '?-?';
            } else {
                $formatter = '?-?-?';
            }

            $groupColumn = 'DATE_FORMAT(created_at, "'.Str::replaceArray('?', ['%Y', '%m', '%d'], $formatter).'")';

            $intervals = [];

            foreach ($datePeriod as $date) {
                $formattedDate = $date->format(Str::replaceArray('?', ['Y', 'm', 'd'], $formatter));

                $intervals[] = [
                    'filter' => $formattedDate,
                    'start'  => $formattedDate,
                ];
            }

            return [
                'group_column' => $groupColumn,
                'intervals'    => $intervals,
            ];
        }
    }

    
    public function getMonthsInterval($startDate, $endDate)
    {
        $intervals = [];

        $totalMonths = $startDate->diffInMonths($endDate) + 1;

        
        if ($totalMonths <= 5) {
            return $intervals;
        }

        for ($i = 0; $i < $totalMonths; $i++) {
            $intervalStartDate = clone $startDate;

            $intervalStartDate->addMonths($i);

            $start = $intervalStartDate->startOfDay();

            $end = ($totalMonths - 1 == $i)
                ? $endDate
                : $intervalStartDate->addMonth()->subDay()->endOfDay();

            $intervals[] = [
                'filter' => $start->month,
                'start'  => $start->format('d M'),
                'end'    => $end->format('d M'),
            ];
        }

        return $intervals;
    }

    
    public function getWeeksInterval($startDate, $endDate)
    {
        $intervals = [];

        $startWeekDay = Carbon::createFromTimeString(core()->xWeekRange($startDate, 0).' 00:00:01');

        $endWeekDay = Carbon::createFromTimeString(core()->xWeekRange($endDate, 1).' 23:59:59');

        $totalWeeks = $startWeekDay->diffInWeeks($endWeekDay);

        
        if ($totalWeeks <= 6) {
            return $intervals;
        }

        for ($i = 0; $i < $totalWeeks; $i++) {
            $intervalStartDate = clone $startDate;

            $intervalStartDate->addWeeks($i);

            $start = $i == 0
                ? $startDate
                : Carbon::createFromTimeString(core()->xWeekRange($intervalStartDate, 0).' 00:00:01');

            $end = ($totalWeeks - 1 == $i)
                ? $endDate
                : Carbon::createFromTimeString(core()->xWeekRange($intervalStartDate->subDay(), 1).' 23:59:59');

            $intervals[] = [
                'filter' => $start->week,
                'start'  => $start->format('d M'),
                'end'    => $end->format('d M'),
            ];
        }

        return $intervals;
    }

    
    public function getDaysInterval($startDate, $endDate)
    {
        $intervals = [];

        $totalDays = $startDate->diffInDays($endDate) + 1;

        for ($i = 0; $i < $totalDays; $i++) {
            $intervalStartDate = clone $startDate;

            $intervalStartDate->addDays($i);

            $intervals[] = [
                'filter' => $intervalStartDate->dayOfYear,
                'start'  => $intervalStartDate->startOfDay()->format('d M'),
                'end'    => $intervalStartDate->endOfDay()->format('d M'),
            ];
        }

        return $intervals;
    }
}
