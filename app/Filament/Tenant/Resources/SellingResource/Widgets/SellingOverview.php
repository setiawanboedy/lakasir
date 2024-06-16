<?php

namespace App\Filament\Tenant\Resources\SellingResource\Widgets;

use App\Models\Tenants\Selling;
use App\Models\Tenants\SellingDetail;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class SellingOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totalRevenue = $this->getTotalRevenue();
        $todaySales = $this->getSalesToday();
        $discountToday = $this->getDiscountToday();

        return [
            can('read revenue overview') ? Stat::make(__('Today total revenue'), $totalRevenue['total_revenue'])
                ->descriptionIcon($totalRevenue['icon'])
                ->description($totalRevenue['description'])
                ->chart([$totalRevenue['yesterdayRevenue'], $totalRevenue['todayRevenue']])
                ->color($totalRevenue['color']) : null,
            can('read sales overview') ? Stat::make(__('Sales today'), $todaySales) : null,
            can('read sales overview') ? Stat::make(__('Discount today'), $discountToday) : null,
        ];
    }

    private function getDiscountToday()
    {
        $startDate = today()->startOfDay();
        $endDate = today()->endOfDay();
        $totalDiscountSellings = Selling::whereBetween('date', [$startDate, $endDate])
            ->sum('discount_price');

        $totalDiscountSellingDetails = SellingDetail::whereHas('selling', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        })->sum('discount_price');

        $totalDiscount = $totalDiscountSellings + $totalDiscountSellingDetails;

        return Number::abbreviate($totalDiscount);
    }

    private function getSalesToday()
    {
        $salesToday = Selling::whereDate('date', today())->count();

        return $salesToday;
    }

    private function getTotalRevenue()
    {
        $yesterdayRevenue = Selling::query()
            ->select(
                DB::raw('(COALESCE(SUM(sellings.discount_price), 0) / 1000) as total_discount_selling'),
            )
            ->addSelect(
                DB::raw('COALESCE(SUM(COALESCE((SELECT SUM(selling_details.cost) as total_cost FROM selling_details WHERE selling_details.selling_id = sellings.id), 0) / 1000), 0) as total_cost')
            )
            ->addSelect(
                DB::raw('COALESCE(SUM(COALESCE((SELECT SUM(selling_details.price) FROM selling_details WHERE selling_details.selling_id = sellings.id), 0) / 1000), 0) as total_price')
            )
            ->addSelect(
                DB::raw('COALESCE(SUM(COALESCE((SELECT COALESCE(SUM(selling_details.discount_price), 0) FROM selling_details WHERE selling_details.selling_id = sellings.id), 0) / 1000), 0) as total_discount_per_item')
            )
            ->isPaid()
            ->whereBetween('created_at', [
                now()->subDay(1)->startOfDay(),
                now()->subDay(1)->endOfDay(),
            ])
            ->first();
        $todayRevenue = Selling::query()
            ->select(
                DB::raw('(COALESCE(SUM(sellings.discount_price), 0) / 1000) as total_discount_selling'),
            )
            ->addSelect(
                DB::raw('COALESCE(SUM(COALESCE((SELECT SUM(selling_details.cost) as total_cost FROM selling_details WHERE selling_details.selling_id = sellings.id), 0) / 1000), 0) as total_cost')
            )
            ->addSelect(
                DB::raw('COALESCE(SUM(COALESCE((SELECT SUM(selling_details.price) FROM selling_details WHERE selling_details.selling_id = sellings.id), 0) / 1000), 0) as total_price')
            )
            ->addSelect(
                DB::raw('COALESCE(SUM(COALESCE((SELECT COALESCE(SUM(selling_details.discount_price), 0) FROM selling_details WHERE selling_details.selling_id = sellings.id), 0) / 1000), 0) as total_discount_per_item')
            )
            ->isPaid()
            ->whereBetween('created_at', [
                now()->startOfDay(),
                now()->endOfDay(),
            ])
            ->first();

        $totalYesterdayRevenue = $yesterdayRevenue->total_price - $yesterdayRevenue->total_cost - $yesterdayRevenue->total_discount_per_item - $yesterdayRevenue->total_discount_selling;
        $totalTodayRevenue = $todayRevenue->total_price - $todayRevenue->total_cost - $todayRevenue->total_discount_per_item - $todayRevenue->total_discount_selling;

        $readable = match (true) {
            $totalTodayRevenue >= 1 => 'K',
            $totalTodayRevenue >= 1000 => 'M',
            $totalTodayRevenue >= 1000000 => 'B',
            default => ''
        };

        $trend = __('sideway');
        $color = 'warning';
        $icon = 'heroicon-m-minus';
        if ($totalYesterdayRevenue > $totalTodayRevenue) {
            $trend = __('decrease');
            $color = 'danger';
            $icon = 'heroicon-m-arrow-trending-down';
        }
        if ($totalYesterdayRevenue < $totalTodayRevenue) {
            $trend = __('increase');
            $color = 'success';
            $icon = 'heroicon-m-arrow-trending-up';
        }

        $prosentase = 0;
        if ($totalYesterdayRevenue) {
            $prosentase = (($totalTodayRevenue - $totalYesterdayRevenue) / $totalYesterdayRevenue) * 100;
        }

        return [
            'total_revenue' => $totalTodayRevenue.$readable,
            'description' => round($prosentase).'% '.$trend,
            'yesterdayRevenue' => $totalYesterdayRevenue,
            'todayRevenue' => $totalTodayRevenue,
            'color' => $color,
            'icon' => $icon,
        ];
    }
}
