<?php

namespace App\Tests\Service;

use App\Service\RecoveryPhraseService;
use PHPUnit\Framework\TestCase;

class RecoveryPhraseServiceTest extends TestCase
{
    private RecoveryPhraseService $service;

    protected function setUp(): void
    {
        $this->service = new RecoveryPhraseService();
    }

    public function testGenerateReturns24Words(): void
    {
        $phrase = $this->service->generate(24);
        $words = explode(' ', $phrase);

        $this->assertCount(24, $words);
    }

    public function testGenerateProducesUniquePhrasesWithHighProbability(): void
    {
        $phrase1 = $this->service->generate(24);
        $phrase2 = $this->service->generate(24);

        $this->assertNotEquals($phrase1, $phrase2);
    }

    public function testValidateAcceptsValidPhrase(): void
    {
        // Valid 24-word phrase from BIP39 wordlist
        $validPhrase = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon art';

        $this->assertTrue($this->service->validate($validPhrase));
    }

    public function testValidateRejectsInvalidWordCount(): void
    {
        $invalidPhrase = 'abandon abandon abandon'; // Only 3 words

        $this->assertFalse($this->service->validate($invalidPhrase));
    }

    public function testValidateRejectsInvalidWords(): void
    {
        // 24 words but with invalid word
        $invalidPhrase = str_repeat('abandon ', 23) . 'invalidword';

        $this->assertFalse($this->service->validate($invalidPhrase));
    }

    public function testHashProducesConsistentOutput(): void
    {
        $phrase = 'test phrase for hashing';
        $hash1 = $this->service->hash($phrase);
        $hash2 = $this->service->hash($phrase);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1)); // SHA-256 produces 64 char hex
    }

    public function testHashProducesDifferentOutputForDifferentInputs(): void
    {
        $phrase1 = 'first test phrase';
        $phrase2 = 'second test phrase';

        $hash1 = $this->service->hash($phrase1);
        $hash2 = $this->service->hash($phrase2);

        $this->assertNotEquals($hash1, $hash2);
    }
}

