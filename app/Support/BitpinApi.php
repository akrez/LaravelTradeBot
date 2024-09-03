<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class BitpinApi
{
    private $cache = [];

    public function getMarkets()
    {
        $cacheKey = 'BitpinApi.getMarkets';

        if (! isset($this->cache[$cacheKey])) {

            if (0) {
                $response = File::json(storage_path('api.bitpin.ir.json'))['v1/mkt/markets'];
            } else {
                $response = (array) Http::get('https://api.bitpin.ir/v1/mkt/markets/')->json();
            }

            if (isset($response['results']) and is_array($response['results'])) {
                $this->cache[$cacheKey] = $response['results'];
            } else {
                $this->cache[$cacheKey] = [];
            }
        }

        return $this->cache[$cacheKey];
    }

    public function getMarketsArray()
    {
        $cacheKey = 'BitpinApi.getMarketsArray';

        $markets = $this->getMarkets();

        if (! isset($this->cache[$cacheKey])) {
            $result = [];
            foreach ($markets as $market) {
                $markeCodeParts = explode('_', $market['code']);
                $result[implode('', $markeCodeParts)] = $market['id'];
            }
            $this->cache[$cacheKey] = $result;
        }

        return $this->cache[$cacheKey];
    }

    public function getMarketsKeyboard($target = null)
    {
        $cacheKey = 'BitpinApi.getMarketsKeyboard.'.$target;

        $markets = $this->getMarkets();

        if (! isset($this->cache[$cacheKey])) {
            $result = [];
            foreach ($markets as $market) {
                $markeCodeParts = explode('_', $market['code']);
                if ($target === null or $markeCodeParts[1] === $target) {
                    $result[] = '+'.implode('', $markeCodeParts);
                }
            }
            $this->cache[$cacheKey] = $result;
        }

        return $this->cache[$cacheKey];
    }

    public function getMarketId($symbol)
    {
        $array = $this->getMarketsArray();

        return empty($array[$symbol]) ? null : $array[$symbol];
    }

    public function getCoin($coin, $iteration, $useCache = true)
    {
        $cacheKey = 'BitpinApi.getCoin.'.$coin.'.'.$iteration;

        if ($useCache and isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        if (true) {
            $url = 'https://api.bitpin.ir/v1/mkt/markets/charts/'.$coin.'/'.$iteration.'/';
            $this->cache[$cacheKey] = (array) Http::get($url)->json();
        } else {
            $this->cache[$cacheKey] = [0 => ['price' => '195422', 'created_at' => 1653630265.283], 1 => ['price' => '197336', 'created_at' => 1653630327.311], 2 => ['price' => '196069', 'created_at' => 1653630399.276], 3 => ['price' => '196832', 'created_at' => 1653630459.311], 4 => ['price' => '198087', 'created_at' => 1653630523.31], 5 => ['price' => '197471', 'created_at' => 1653630587.294], 6 => ['price' => '197122', 'created_at' => 1653630650.336], 7 => ['price' => '197128', 'created_at' => 1653630713.305], 8 => ['price' => '197309', 'created_at' => 1653630776.255], 9 => ['price' => '197309', 'created_at' => 1653630840.355], 10 => ['price' => '197246', 'created_at' => 1653630903.183], 11 => ['price' => '196939', 'created_at' => 1653630963.354], 12 => ['price' => '197090', 'created_at' => 1653631025.381], 13 => ['price' => '197122', 'created_at' => 1653631090.358], 14 => ['price' => '197208', 'created_at' => 1653631154.405], 15 => ['price' => '197566', 'created_at' => 1653631216.413], 16 => ['price' => '196965', 'created_at' => 1653631283.248], 17 => ['price' => '196777', 'created_at' => 1653631346.177], 18 => ['price' => '196777', 'created_at' => 1653631409.443], 19 => ['price' => '196182', 'created_at' => 1653631481.447], 20 => ['price' => '195868', 'created_at' => 1653631553.411], 21 => ['price' => '196182', 'created_at' => 1653631625.476], 22 => ['price' => '195843', 'created_at' => 1653631698.483], 23 => ['price' => '196144', 'created_at' => 1653631770.35], 24 => ['price' => '196282', 'created_at' => 1653631842.51], 25 => ['price' => '197727', 'created_at' => 1653631914.51], 26 => ['price' => '198494', 'created_at' => 1653631987.493], 27 => ['price' => '197903', 'created_at' => 1653632059.418], 28 => ['price' => '197865', 'created_at' => 1653632131.468], 29 => ['price' => '196903', 'created_at' => 1653632204.54], 30 => ['price' => '196749', 'created_at' => 1653632275.549], 31 => ['price' => '196556', 'created_at' => 1653632348.341], 32 => ['price' => '196373', 'created_at' => 1653632419.658], 33 => ['price' => '195749', 'created_at' => 1653632490.774], 34 => ['price' => '197202', 'created_at' => 1653632562.716], 35 => ['price' => '197328', 'created_at' => 1653632634.883], 36 => ['price' => '198217', 'created_at' => 1653632694.887], 37 => ['price' => '197875', 'created_at' => 1653632757.798], 38 => ['price' => '197702', 'created_at' => 1653632821.918], 39 => ['price' => '197376', 'created_at' => 1653632883.929], 40 => ['price' => '197301', 'created_at' => 1653632949.874], 41 => ['price' => '197695', 'created_at' => 1653633013.905], 42 => ['price' => '197301', 'created_at' => 1653633074.956], 43 => ['price' => '197945', 'created_at' => 1653633138.899], 44 => ['price' => '198245', 'created_at' => 1653633198.965], 45 => ['price' => '198699', 'created_at' => 1653633262.786], 46 => ['price' => '200096', 'created_at' => 1653633326.979], 47 => ['price' => '200434', 'created_at' => 1653633390.978], 48 => ['price' => '200280', 'created_at' => 1653633454.982], 49 => ['price' => '200012', 'created_at' => 1653633515.973], 50 => ['price' => '200286', 'created_at' => 1653633580.345], 51 => ['price' => '200280', 'created_at' => 1653633643.512], 52 => ['price' => '199968', 'created_at' => 1653633714.52], 53 => ['price' => '199463', 'created_at' => 1653633787.738], 54 => ['price' => '199604', 'created_at' => 1653633859.54], 55 => ['price' => '199916', 'created_at' => 1653633930.479], 56 => ['price' => '200528', 'created_at' => 1653634003.535]];
        }

        return $this->cache[$cacheKey];
    }
}
