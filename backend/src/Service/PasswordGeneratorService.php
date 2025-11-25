<?php

namespace App\Service;

class PasswordGeneratorService
{
    private array $adjectives = [
        'Quick', 'Silent', 'Bright', 'Swift', 'Bold', 'Clever',
        'Brave', 'Calm', 'Noble', 'Wise', 'Sharp', 'Strong',
        'Fierce', 'Gentle', 'Lucky', 'Proud', 'Happy', 'Mystic'
    ];

    private array $nouns = [
        'Tiger', 'Eagle', 'River', 'Storm', 'Phoenix', 'Dragon',
        'Wolf', 'Falcon', 'Thunder', 'Lightning', 'Ocean', 'Mountain',
        'Forest', 'Shadow', 'Star', 'Moon', 'Sun', 'Wind'
    ];

    private array $words = [
        'Correct', 'Horse', 'Battery', 'Staple', 'Purple', 'Monkey',
        'Dishwasher', 'Elephant', 'Giraffe', 'Penguin', 'Rainbow', 'Crystal',
        'Diamond', 'Sapphire', 'Emerald', 'Ruby'
    ];

    public function generate(): string
    {
        $word1 = $this->words[array_rand($this->words)];
        $word2 = $this->adjectives[array_rand($this->adjectives)];
        $word3 = $this->nouns[array_rand($this->nouns)];
        $number = random_int(10, 99);

        return "{$word1}-{$word2}-{$word3}-{$number}";
    }
}

