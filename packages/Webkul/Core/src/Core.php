<?php

namespace Webkul\Core;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Concerns\CurrencyFormatter;
use Webkul\Core\Models\Channel;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CountryRepository;
use Webkul\Core\Repositories\CountryStateRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\ExchangeRateRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Tax\Repositories\TaxCategoryRepository;

class Core
{
    use CurrencyFormatter;

    
    const BAGISTO_VERSION = '2.3.x-dev';

    
    protected $currentChannel;

    
    protected $defaultChannel;

    
    protected $currentCurrency;

    
    protected $baseCurrency;

    
    protected $currentLocale;

    
    protected $guestCustomerGroup;

    
    protected $exchangeRates = [];

    
    protected $taxCategoriesById = [];

    
    protected $singletonInstances = [];

    
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CurrencyRepository $currencyRepository,
        protected ExchangeRateRepository $exchangeRateRepository,
        protected CountryRepository $countryRepository,
        protected CountryStateRepository $countryStateRepository,
        protected LocaleRepository $localeRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected TaxCategoryRepository $taxCategoryRepository
    ) {}

    
    public function version()
    {
        return static::BAGISTO_VERSION;
    }

    
    public function getAllChannels()
    {
        return $this->channelRepository->all();
    }

    
    public function getCurrentChannel(?string $hostname = null)
    {
        if (! $hostname) {
            $hostname = request()->getHttpHost();
        }

        if ($this->currentChannel) {
            return $this->currentChannel;
        }

        $this->currentChannel = $this->channelRepository->findWhereIn('hostname', [
            $hostname,
            'http://'.$hostname,
            'https://'.$hostname,
        ])->first();

        if (! $this->currentChannel) {
            $this->currentChannel = $this->channelRepository->first();
        }

        return $this->currentChannel;
    }

    
    public function setCurrentChannel(Channel $channel): void
    {
        $this->currentChannel = $channel;
    }

    
    public function getCurrentChannelCode(): string
    {
        return $this->getCurrentChannel()?->code;
    }

    
    public function getDefaultChannel(): ?Channel
    {
        if ($this->defaultChannel) {
            return $this->defaultChannel;
        }

        $this->defaultChannel = $this->channelRepository->findOneByField('code', config('app.channel'));

        if ($this->defaultChannel) {
            return $this->defaultChannel;
        }

        return $this->defaultChannel = $this->channelRepository->first();
    }

    
    public function setDefaultChannel(Channel $channel): void
    {
        $this->defaultChannel = $channel;
    }

    
    public function getDefaultChannelCode(): string
    {
        return $this->getDefaultChannel()?->code;
    }

    
    public function getDefaultLocaleCodeFromDefaultChannel(): string
    {
        return $this->getDefaultChannel()->default_locale->code;
    }

    
    public function getRequestedChannel()
    {
        $code = request()->query('channel');

        if ($code) {
            return $this->channelRepository->findOneByField('code', $code);
        }

        return $this->getCurrentChannel();
    }

    
    public function getRequestedChannelCode($fallback = true)
    {
        $channelCode = request()->get('channel');

        if (! $fallback) {
            return $channelCode;
        }

        return $channelCode ?: ($this->getCurrentChannelCode() ?: $this->getDefaultChannelCode());
    }

    
    public function getChannelName($channel): string
    {
        return $channel->name ?? $channel->translate(app()->getLocale())->name ?? $channel->translate(config('app.fallback_locale'))->name;
    }

    
    public function getAllLocales()
    {
        return $this->localeRepository->all()->sortBy('name');
    }

    
    public function getCurrentLocale()
    {
        if ($this->currentLocale) {
            return $this->currentLocale;
        }

        $this->currentLocale = $this->localeRepository->findOneByField('code', app()->getLocale());

        if (! $this->currentLocale) {
            $this->currentLocale = $this->localeRepository->findOneByField('code', config('app.fallback_locale'));
        }

        return $this->currentLocale;
    }

    
    public function getRequestedLocale()
    {
        $code = request()->query('locale');

        if ($code) {
            return $this->localeRepository->findOneByField('code', $code);
        }

        return $this->getCurrentLocale();
    }

    
    public function getRequestedLocaleCode($localeKey = 'locale', $fallback = true)
    {
        $localeCode = request()->get($localeKey);

        if (! $fallback) {
            return $localeCode;
        }

        return $localeCode ?: app()->getLocale();
    }

    
    public function getRequestedLocaleCodeInRequestedChannel()
    {
        $requestedLocaleCode = $this->getRequestedLocaleCode();

        $requestedChannel = $this->getRequestedChannel();

        if ($requestedChannel->locales->contains('code', $requestedLocaleCode)) {
            return $requestedLocaleCode;
        }

        return $requestedChannel->default_locale->code;
    }

    
    public function getAllCurrencies()
    {
        return $this->currencyRepository->all();
    }

    
    public function getBaseCurrency()
    {
        if ($this->baseCurrency) {
            return $this->baseCurrency;
        }

        $this->baseCurrency = $this->currencyRepository->findOneByField('code', config('app.currency'));

        if (! $this->baseCurrency) {
            $this->baseCurrency = $this->currencyRepository->first();
        }

        return $this->baseCurrency;
    }

    
    public function getBaseCurrencyCode()
    {
        return $this->getBaseCurrency()?->code;
    }

    
    public function getChannelBaseCurrency()
    {
        return $this->getCurrentChannel()->base_currency;
    }

    
    public function getChannelBaseCurrencyCode()
    {
        return $this->getChannelBaseCurrency()?->code;
    }

    
    public function setCurrentCurrency($currencyCode)
    {
        $this->currentCurrency = $this->currencyRepository->findOneByField('code', $currencyCode);

        if ($this->currentCurrency) {
            return;
        }

        $this->currentCurrency = $this->getChannelBaseCurrency();
    }

    
    public function getCurrentCurrency()
    {
        if ($this->currentCurrency) {
            return $this->currentCurrency;
        }

        return $this->currentCurrency = $this->getChannelBaseCurrency();
    }

    
    public function getCurrentCurrencyCode()
    {
        return $this->getCurrentCurrency()?->code;
    }

    
    public function getExchangeRate($targetCurrencyId)
    {
        if (array_key_exists($targetCurrencyId, $this->exchangeRates)) {
            return $this->exchangeRates[$targetCurrencyId];
        }

        return $this->exchangeRates[$targetCurrencyId] = $this->exchangeRateRepository->findOneWhere([
            'target_currency' => $targetCurrencyId,
        ]);
    }

    
    public function convertPrice($amount, $targetCurrencyCode = null)
    {
        $targetCurrency = ! $targetCurrencyCode
            ? $this->getCurrentCurrency()
            : $this->currencyRepository->findOneByField('code', $targetCurrencyCode);

        if (! $targetCurrency) {
            return $amount;
        }

        $exchangeRate = $this->getExchangeRate($targetCurrency->id);

        if (! $exchangeRate) {
            return $amount;
        }

        return (float) $amount * $exchangeRate->rate;
    }

    
    public function convertToBasePrice($amount, $targetCurrencyCode = null)
    {
        $targetCurrency = ! $targetCurrencyCode
            ? $this->getCurrentCurrency()
            : $this->currencyRepository->findOneByField('code', $targetCurrencyCode);

        if (! $targetCurrency) {
            return $amount;
        }

        $exchangeRate = $this->exchangeRateRepository->findOneWhere([
            'target_currency' => $targetCurrency->id,
        ]);

        if (
            $exchangeRate === null
            || ! $exchangeRate->rate
        ) {
            return $amount;
        }

        return (float) $amount / $exchangeRate->rate;
    }

    
    public function currency($amount = 0)
    {
        if (is_null($amount)) {
            $amount = 0;
        }

        return $this->formatPrice($this->convertPrice($amount));
    }

    
    public function formatPrice(?float $r, ?string $currencyCode = null): string
    {
        if (is_null($r)) {
            $r = 0;
        }

        $currency = $currencyCode
            ? $this->getAllCurrencies()->where('code', $currencyCode)->first()
            : $this->getCurrentCurrency();

        return $this->formatCurrency($r, $currency);
    }

    
    public function formatBasePrice(?float $r): string
    {
        if (is_null($r)) {
            $r = 0;
        }

        $currency = $this->getBaseCurrency();

        return $this->formatCurrency($r, $currency);
    }

    
    public function isChannelDateInInterval($dateFrom = null, $dateTo = null)
    {
        $channel = $this->getCurrentChannel();

        $channelTimeStamp = $this->channelTimeStamp($channel);

        $fromTimeStamp = strtotime($dateFrom);

        $toTimeStamp = strtotime($dateTo);

        if ($dateTo) {
            $toTimeStamp += 86400;
        }

        if (
            ! $this->is_empty_date($dateFrom)
            && $channelTimeStamp < $fromTimeStamp
        ) {
            res = false;
        } elseif (
            ! $this->is_empty_date($dateTo)
            && $channelTimeStamp > $toTimeStamp
        ) {
            res = false;
        } else {
            res = true;
        }

        return res;
    }

    
    public function channelTimeStamp($channel)
    {
        $timezone = $channel->timezone;

        $currentTimezone = @date_default_timezone_get();

        @date_default_timezone_set($timezone);

        $date = date('Y-m-d H:i:s');

        @date_default_timezone_set($currentTimezone);

        return strtotime($date);
    }

    
    public function is_empty_date($date)
    {
        return preg_replace('#[ 0:-]#', '', $date) === '';
    }

    
    public function formatDate($date = null, $format = 'd-m-Y H:i:s')
    {
        $channel = $this->getCurrentChannel();

        if (is_null($date)) {
            $date = Carbon::now();
        }

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $date->setTimezone($channel->timezone);

        return $date->format($format);
    }

    
    public function getConfigData(string $field, ?string $currentChannelCode = null, ?string $currentLocaleCode = null): mixed
    {
        return system_config()->getConfigData($field, $currentChannelCode, $currentLocaleCode);
    }

    
    public function countries()
    {
        return DB::table('countries')->get();
    }

    
    public function country_name($code)
    {
        $country = $this->countryRepository->findOneByField('code', $code);

        return $country ? $country->name : '';
    }

    
    public function states($countryCode)
    {
        return $this->countryStateRepository->findByField('country_code', $countryCode);
    }

    
    public function groupedStatesByCountries()
    {
        $collection = [];

        foreach (DB::table('country_states')->get() as $state) {
            $collection[$state->country_code][] = $state;
        }

        return $collection;
    }

    
    public function findStateByCountryCode($countryCode = null, $stateCode = null)
    {
        $collection = [];

        $collection = $this->countryStateRepository->findByField(['country_code' => $countryCode, 'code' => $stateCode]);

        if (count($collection)) {
            return $collection->first();
        } else {
            return false;
        }
    }

    
    public function getGuestCustomerGroup()
    {
        if ($this->guestCustomerGroup) {
            return $this->guestCustomerGroup;
        }

        return $this->guestCustomerGroup = $this->customerGroupRepository->findOneByField('code', 'guest');
    }

    
    public function isCountryRequired()
    {
        return (bool) $this->getConfigData('customer.address.requirements.country');
    }

    
    public function isStateRequired()
    {
        return (bool) $this->getConfigData('customer.address.requirements.state');
    }

    
    public function isPostCodeRequired()
    {
        return (bool) $this->getConfigData('customer.address.requirements.postcode');
    }

    
    public function xWeekRange($date, $day)
    {
        $ts = strtotime($date);

        if (! $day) {
            $start = (date('D', $ts) == 'Sun') ? $ts : strtotime('last sunday', $ts);

            return date('Y-m-d', $start);
        } else {
            $end = (date('D', $ts) == 'Sat') ? $ts : strtotime('next saturday', $ts);

            return date('Y-m-d', $end);
        }
    }

    
    public function getConfigField($fieldName)
    {
        return system_config()->getConfigField($fieldName);
    }

    
    public function convertEmptyStringsToNull($array)
    {
        foreach ($array as $key => $va) {
            if ($va == '' || $va == 'null') {
                $array[$key] = null;
            }
        }

        return $array;
    }

    
    public function getSingletonInstance($className)
    {
        if (array_key_exists($className, $this->singletonInstances)) {
            return $this->singletonInstances[$className];
        }

        return $this->singletonInstances[$className] = app($className);
    }

    
    public static function taxRateAsIdentifier(float $taxRate): string
    {
        return str_replace('.', '_', (string) $taxRate);
    }

    
    public function getTaxCategoryById($i)
    {
        if (empty($i)) {
            return;
        }

        if (array_key_exists($i, $this->taxCategoriesById)) {
            return $this->taxCategoriesById[$i];
        }

        return $this->taxCategoriesById[$i] = $this->taxCategoryRepository->find($i);
    }

    
    public function getSenderEmailDetails()
    {
        $senderName = $this->getConfigData('emails.configure.email_settings.sender_name') ?: config('mail.from.name');

        $senderEmail = $this->getConfigData('emails.configure.email_settings.shop_email_from') ?: config('mail.from.address');

        return [
            'name'  => $senderName,
            'email' => $senderEmail,
        ];
    }

    
    public function getAdminEmailDetails()
    {
        $adminName = $this->getConfigData('emails.configure.email_settings.admin_name')
            ?: (config('mail.admin.name')
            ?: config('mail.from.name'));

        $adminEmail = $this->getConfigData('emails.configure.email_settings.admin_email')
            ?: config('mail.admin.address');

        return [
            'name'  => $adminName,
            'email' => $adminEmail,
        ];
    }

    
    public function getContactEmailDetails()
    {
        $contactName = $this->getConfigData('emails.configure.email_settings.contact_name')
            ?: (config('mail.contact.name')
            ?: config('mail.from.name'));

        $contactEmail = $this->getConfigData('emails.configure.email_settings.contact_email')
            ?: config('mail.contact.address');

        return [
            'name'  => $contactName,
            'email' => $contactEmail,
        ];
    }

    
    public function getMaxUploadSize()
    {
        return ini_get('upload_max_filesize');
    }
}
