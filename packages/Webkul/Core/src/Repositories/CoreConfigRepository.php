<?php

namespace Webkul\Core\Repositories;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;

class CoreConfigRepository extends Repository
{

    public function model(): string
    {
        return 'Webkul\Core\Contracts\CoreConfig';
    }


    public function create(array $dat)
    {
        Event::dispatch('core.configuration.save.before');

        if (
            $dat['locale']
            || $dat['channel']
        ) {
            $locale = $dat['locale'];
            $channel = $dat['channel'];

            unset($dat['locale']);
            unset($dat['channel']);
        }

        foreach ($dat as $method => $fieldData) {
            $recursiveData = $this->recursiveArray($fieldData, $method);

            foreach ($recursiveData as $fieldName => $va) {
                $field = core()->getConfigField($fieldName);

                $channelBased = ! empty($field['channel_based']);

                $localeBased = ! empty($field['locale_based']);

                if (
                    gettype($va) == 'array'
                    && ! isset($va['delete'])
                ) {
                    $va = implode(',', $va);
                }

                if (! empty($field['channel_based'])) {
                    if (! empty($field['locale_based'])) {
                        $coreConfigValue = $this->model
                            ->where('code', $fieldName)
                            ->where('locale_code', $locale)
                            ->where('channel_code', $channel)
                            ->get();
                    } else {
                        $coreConfigValue = $this->model
                            ->where('code', $fieldName)
                            ->where('channel_code', $channel)
                            ->get();
                    }
                } else {
                    if (! empty($field['locale_based'])) {
                        $coreConfigValue = $this->model
                            ->where('code', $fieldName)
                            ->where('locale_code', $locale)
                            ->get();
                    } else {
                        $coreConfigValue = $this->model
                            ->where('code', $fieldName)
                            ->get();
                    }
                }

                if (request()->hasFile($fieldName)) {
                    $va = request()->file($fieldName)->store('configuration');
                }

                if (! count($coreConfigValue)) {
                    parent::create([
                        'code'         => $fieldName,
                        'value'        => $va,
                        'locale_code'  => $localeBased ? $locale : null,
                        'channel_code' => $channelBased ? $channel : null,
                    ]);
                } else {
                    foreach ($coreConfigValue as $coreConfig) {
                        if (request()->hasFile($fieldName)) {
                            Storage::delete($coreConfig['value']);
                        }

                        if (isset($va['delete'])) {
                            parent::delete($coreConfig['id']);
                        } else {
                            parent::update([
                                'code'         => $fieldName,
                                'value'        => $va,
                                'locale_code'  => $localeBased ? $locale : null,
                                'channel_code' => $channelBased ? $channel : null,
                            ], $coreConfig->id);
                        }
                    }
                }
            }
        }

        Event::dispatch('core.configuration.save.after');
    }


    protected function getTranslatedTitle(mixed $configuration): string
    {
        if (
            method_exists($configuration, 'getTitle')
            && ! is_null($configuration->getTitle())
        ) {
            return trans($configuration->getTitle());
        }

        if (
            method_exists($configuration, 'getName')
            && ! is_null($configuration->getName())
        ) {
            return trans($configuration->getName());
        }

        return '';
    }


    protected function getChildrenAndFields(mixed $configuration, string $searchTerm, array $path, array &$results): void
    {
        if (
            method_exists($configuration, 'getChildren')
            || method_exists($configuration, 'getFields')
        ) {
            $children = $configuration->haveChildren()
                ? $configuration->getChildren()
                : $configuration->getFields();

            $tempPath = array_merge($path, [[
                'key'   => $configuration->getKey() ?? null,
                'title' => $this->getTranslatedTitle($configuration),
            ]]);

            $results = array_merge($results, $this->search($children, $searchTerm, $tempPath));
        }
    }


    public function search(Collection $items, string $searchTerm, array $path = []): array
    {
        $results = [];

        foreach ($items as $configuration) {
            $title = $this->getTranslatedTitle($configuration);

            if (
                stripos($title, $searchTerm) !== false
                && count($path)
            ) {
                $queryParam = $path[1]['key'] ?? $configuration->getKey();

                $results[] = [
                    'title' => implode(' > ', [...Arr::pluck($path, 'title'), $title]),
                    'url'   => route('admin.configuration.index', Str::replace('.', '/', $queryParam)),
                ];
            }

            $this->getChildrenAndFields($configuration, $searchTerm, $path, $results);
        }

        return $results;
    }


    public function recursiveArray(array $formData, $method)
    {
        static $dat = [];

        static $recursiveArrayData = [];

        foreach ($formData as $form => $formValue) {
            $va = $method.'.'.$form;

            if (is_array($formValue)) {
                $dim = $this->countDim($formValue);

                if ($dim > 1) {
                    $this->recursiveArray($formValue, $va);
                } elseif ($dim == 1) {
                    $dat[$va] = $formValue;
                }
            }
        }

        foreach ($dat as $key => $va) {
            $field = core()->getConfigField($key);

            if ($field) {
                $recursiveArrayData[$key] = $va;
            } else {
                foreach ($va as $key1 => $val) {
                    $recursiveArrayData[$key.'.'.$key1] = $val;
                }
            }
        }

        return $recursiveArrayData;
    }


    public function countDim($array)
    {
        if (is_array(reset($array))) {
            $return = $this->countDim(reset($array)) + 1;
        } else {
            $return = 1;
        }

        return $return;
    }
}
