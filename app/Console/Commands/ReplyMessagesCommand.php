<?php

namespace App\Console\Commands;

use App\Models\Coin;
use App\Support\BitpinApi;
use App\Support\Sparkline;
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
            $minMaxResult = $this->calcMinMaxMessage($hours);
            if ($minMaxResult) {
                $chats = Coin::query()->where('coin_id', $coin->coin_id)->get();
                $text = implode("\n", [
                    ($minMaxResult['is_min'] ? '🔴' : '🟢').' #'.$coin->coin_name,
                    '⬆️ '.$minMaxResult['max_hour']['price'],
                    ($minMaxResult['is_min'] ? '➖' : '➕').' '.($minMaxResult['max_hour']['price'] - $minMaxResult['min_hour']['price']),
                    '⬇️ '.$minMaxResult['min_hour']['price'],
                    date('Y-m-d H:i:s'),
                    'https://bitpin.ir/coin/'.$coin->coin_name,
                ]);
                $gdImage = (new Sparkline)->generate(
                    array_values($minMaxResult['chart_data']),
                    720,
                    240,
                    ($minMaxResult['is_min'] ? 'e54a5c' : '2bc890')
                );
                //
                foreach ($chats as $chat) {
                    try {
                        $telegramApi->sendPhoto(
                            $chat->chat_id,
                            $this->readStreamAsPng($gdImage),
                            $text,
                            $telegramApi->getDefaultParameter()
                        );
                    } catch (\Throwable $th) {
                        $telegramApi->sendMessage(
                            $chat->chat_id,
                            $text,
                            $telegramApi->getDefaultParameter()
                        );
                    }
                }
            }
        }
    }

    public function readStreamAsPng($image)
    {
        $stream = fopen('php://temp', 'r+');
        imagepng($image, $stream);
        rewind($stream);

        return stream_get_contents($stream);
    }

    public function calcMinMaxMessage($hours)
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
