<?php

namespace App\Tests\Service;

use App\Service\PasswordGeneratorService;
use PHPUnit\Framework\TestCase;

class PasswordGeneratorServiceTest extends TestCase
{
    private PasswordGeneratorService $service;

    protected function setUp(): void
    {
        $this->service = new PasswordGeneratorService();
    }

    public function testGenerateReturnsValidPassword(): void
    {
        $password = $this->service->generate();

        // Should contain 4 parts separated by hyphens
        $parts = explode('-', $password);
        $this->assertCount(4, $parts);

        // Last part should be a number
        $this->assertMatchesRegularExpression('/^\d{2}$/', $parts[3]);

        // Should be readable format
        $this->assertGreaterThan(10, strlen($password));
    }

    public function testGenerateProducesDifferentPasswords(): void
    {
        $password1 = $this->service->generate();
        $password2 = $this->service->generate();

        $this->assertNotEquals($password1, $password2);
    }

    public function testGeneratedPasswordFormat(): void
    {
        $password = $this->service->generate();

        // Format: Word-Adjective-Noun-Number
        $this->assertMatchesRegularExpression(
            '/^[A-Z][a-z]+-[A-Z][a-z]+-[A-Z][a-z]+-\d{2}$/',
            $password
        );
    }
}


