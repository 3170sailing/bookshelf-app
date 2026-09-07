<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = collect([
            User::where('email', 'yamada@example.com')->firstOrFail(),
            User::where('email', 'suzuki@example.com')->firstOrFail(),
            User::where('email', 'tanaka@example.com')->firstOrFail(),
            User::where('email', 'sato@example.com')->firstOrFail(),
            User::where('email', 'takahashi@example.com')->firstOrFail(),
        ]);

        $books = Book::orderBy('id')->get();

        $reviews = [
            [0, 0, 5, '猫の視点から描かれる物語が面白かったです。'],
            [1, 0, 4, '独特な語り口で楽しく読めました。'],
            [2, 0, 4, '時代を感じながら楽しめる作品でした。'],

            [0, 1, 5, '人との接し方について多くの学びがありました。'],
            [2, 1, 4, '仕事でも活かせる内容だと思います。'],
            [3, 1, 5, '何度も読み返したい一冊です。'],

            [1, 2, 5, 'コードを書くうえでとても参考になりました。'],
            [3, 2, 4, '読みやすく実践的な内容でした。'],
            [4, 2, 5, 'プログラミング学習中の人にもおすすめです。'],

            [0, 3, 5, '考え方を見直すきっかけになりました。'],
            [1, 3, 4, '日常生活にも取り入れたい内容でした。'],
            [4, 3, 4, '少し難しい部分もありましたが勉強になりました。'],

            [2, 4, 4, 'テンポがよく読みやすかったです。'],
            [3, 4, 5, '主人公の個性が印象に残りました。'],
            [4, 4, 3, '昔の作品ですが楽しく読めました。'],

            [0, 5, 5, '人類の歴史を広い視点で学べました。'],
            [2, 5, 4, 'ボリュームがありますが興味深い内容でした。'],
            [4, 5, 5, '歴史に興味を持つきっかけになりました。'],

            [1, 6, 5, 'きれいなコードについて深く学べました。'],
            [2, 6, 4, '実際の開発で意識したい内容が多かったです。'],
            [3, 6, 5, 'エンジニアとして読んでおきたい一冊だと思います。'],

            [0, 7, 4, '人間関係について考えさせられました。'],
            [3, 7, 5, '考え方が前向きになる本でした。'],
            [4, 7, 3, '難しい部分もありましたが参考になりました。'],

            [1, 8, 4, '短くて読みやすい作品でした。'],
            [2, 8, 3, '独特な世界観が印象的でした。'],

            [0, 9, 5, 'データを見るときの考え方が変わりました。'],
            [3, 9, 5, '具体例が多く、とても分かりやすかったです。'],
            [4, 9, 4, '思い込みに気づかされる内容でした。'],

            [1, 10, 4, '物流の歴史を知ることができて面白かったです。'],
            [2, 10, 3, '専門的ですが興味深く読めました。'],
            [4, 10, 4, '普段意識しないコンテナの重要性が分かりました。'],
        ];

        foreach ($reviews as [$userIndex, $bookIndex, $rating, $comment]) {
            Review::create([
                'user_id' => $users[$userIndex]->id,
                'book_id' => $books[$bookIndex]->id,
                'rating' => $rating,
                'comment' => $comment,
            ]);
        }
    }
}
