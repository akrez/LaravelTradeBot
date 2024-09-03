<?php

namespace App\Console\Commands;

use App\Models\Coin;
use App\Support\BitpinApi;
use App\Support\Chart;
use App\Support\TelegramApi;
use Illuminate\Console\Command;

class ReplyMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reply-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reply Messages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('telegramapi.token');
        $telegramApi = new TelegramApi($token);
        $bitpinApi = new BitpinApi;
        //
        $coins = Coin::query()->groupBy('coin_id')->get();
        foreach ($coins as $coin) {
            $hours = $bitpinApi->getCoin($coin->coin_id, 'hour');
            $mum = $this->calcMinMaxMessage($hours, $coin->coin_id, $coin->coin_name);
            if ($mum) {
                $chats = Coin::query()->where('coin_id', $coin->coin_id)->get();
                foreach ($chats as $chat) {
                    $text = [];
                    $text[] = ($mum['is_min'] ? '🔴' : '🟢').' #'.$coin->coin_name;
                    $text[] = '⬆️ '.$mum['max_hour']['price'];
                    $text[] = ($mum['is_min'] ? '➖' : '➕').' '.($mum['max_hour']['price'] - $mum['min_hour']['price']);
                    $text[] = '⬇️ '.$mum['min_hour']['price'];
                    $text[] = date('Y-m-d H:i:s');
                    $text[] = 'https://bitpin.ir/coin/'.$coin->coin_name;
                    //
                    $telegramApi->sendMessage($chat->chat_id, implode("\n", $text));
                }
            }
        }
    }

    public function calcMinMaxMessage($hours, $coinKey, $coinName)
    {
        $chartData = [];
        $hasMin = false;
        $minHour = null;
        $hasMax = false;
        $maxHour = null;
        //
        foreach ($hours as $hour) {
            $chartData[date('H:i', $hour['created_at'])] = $hour['price'];
            //
            $hasMin = false;
            if ($minHour === null or $hour['price'] < $minHour['price']) {
                $hasMin = true;
                $minHour = $hour;
            }
            //
            $hasMax = false;
            if ($maxHour === null or $maxHour['price'] < $hour['price']) {
                $hasMax = true;
                $maxHour = $hour;
            }
        }
        //
        if (
            $hasMin !== $hasMax
            && $minHour
            && $maxHour
            && ($minHour['created_at'] !== $maxHour['created_at'])
        ) {
            return [
                'is_min' => $hasMin,
                'hour' => ($hasMin ? $minHour : $maxHour),
                'min_hour' => $minHour,
                'max_hour' => $maxHour,
                'chart_data' => $chartData,
            ];
        }

        return null;
    }

    public function generateMinMaxMessage($hours, $coinKey, $coinName)
    {
        $chartData = [];

        $isMin = false;
        $min = null;
        $isMax = false;
        $max = null;

        $direction = 0;
        $lastHour = null;

        foreach ($hours as $hour) {
            //
            $chartData[date('H:i', $hour['created_at'])] = $hour['price'];
            //
            $isMin = ($min === null or $hour['price'] < $min);
            if ($isMin) {
                $min = $hour['price'];
            }
            //
            $isMax = ($max === null or $max < $hour['price']);
            if ($isMax) {
                $max = $hour['price'];
            }
            //
            if (isset($lastHour['price'])) {
                if ($lastHour['price'] == $hour['price']) {
                    $direction = 0;
                } elseif ($lastHour['price'] < $hour['price']) {
                    $direction = +1;
                } else {
                    $direction = -1;
                }
            }
            $lastHour = $hour;
        }

        if ($isMin or $isMax) {
            $message = [];
            $message[] = ($isMin ? '🔴' : '').($isMax ? '🟢' : '').' #'.$coinName;
            $message[] = '⬆️ '.$max;
            $message[] = ($isMin ? '➖' : '➕').' '.($max - $min);
            $message[] = '⬇️ '.$min;
            $message[] = date('Y-m-d H:i:s');
            $message[] = 'https://bitpin.ir/coin/'.$coinName;

            return [
                'message' => implode("\n", $message),
                'is_update' => (($isMin and $direction === -1) or ($isMax and $direction === +1)),
                'chart_data' => $chartData,
            ];
        }

        return null;
    }

    public function replyMessages(string $token): array
    {

        $userCoinsDbRows = Db::read('user_coins', ' provider = :provider ', [
            ':provider' => Telegram::getProviderName(),
        ]);
        $userCoins = [];
        foreach ($userCoinsDbRows as $userCoinsDbRow) {
            $userCoins[$userCoinsDbRow['coin_id'].'-'.$userCoinsDbRow['coin_name']][] = $userCoinsDbRow;
        }
        foreach ($userCoins as $userCoinInfo => $userChats) {
            [$coinKey, $coinName] = explode('-', $userCoinInfo, 2);
            $minMaxMessage = generateMinMaxMessage($coinKey, $coinName);
            if ($minMaxMessage) {
                $chartImage = Chart::getCurlFile($minMaxMessage['chart_data'], 600, 250, [25, 25, 25, 75]);
                foreach ($userChats as $userChat) {
                    @$result = Telegram::send($params['telegramToken'], $userChat['chat_id'], $minMaxMessage['message'], $chartImage);
                }
            }
        }
    }
}
