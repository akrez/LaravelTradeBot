<?php

namespace App\Console\Commands;

use App\Models\Coin;
use App\Support\BitpinApi;
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
            $minMaxResult = $this->calcMinMaxMessage($hours, $coin->coin_id, $coin->coin_name);
            if ($minMaxResult) {
                $chats = Coin::query()->where('coin_id', $coin->coin_id)->get();
                foreach ($chats as $chat) {
                    $text = [];
                    $text[] = ($minMaxResult['is_min'] ? '🔴' : '🟢').' #'.$coin->coin_name;
                    $text[] = '⬆️ '.$minMaxResult['max_hour']['price'];
                    $text[] = ($minMaxResult['is_min'] ? '➖' : '➕').' '.($minMaxResult['max_hour']['price'] - $minMaxResult['min_hour']['price']);
                    $text[] = '⬇️ '.$minMaxResult['min_hour']['price'];
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
}
