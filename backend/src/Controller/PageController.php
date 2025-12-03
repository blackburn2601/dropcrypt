<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    /**
     * Account creation page
     */
    #[Route('/account/create', name: 'page_account_create', methods: ['GET'])]
    public function accountCreate(): Response
    {
        return $this->render('account/create.html.twig');
    }

    /**
     * Login page
     */
    #[Route('/account/login', name: 'page_account_login', methods: ['GET'])]
    public function accountLogin(): Response
    {
        return $this->render('account/login.html.twig');
    }

    /**
     * Account recovery page
     */
    #[Route('/account/recover', name: 'page_account_recover', methods: ['GET'])]
    public function accountRecover(): Response
    {
        return $this->render('account/recover.html.twig');
    }

    /**
     * Settings page
     */
    #[Route('/account/settings', name: 'page_account_settings', methods: ['GET'])]
    public function accountSettings(): Response
    {
        return $this->render('account/settings.html.twig');
    }

    /**
     * Dashboard - main messaging hub
     */
    #[Route('/dashboard', name: 'page_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('chat/dashboard.html.twig');
    }

    /**
     * Conversation thread with specific user
     */
    #[Route('/chat/thread/{userId}', name: 'page_chat_thread', methods: ['GET'])]
    public function chatThread(string $userId): Response
    {
        return $this->render('chat/thread.html.twig', [
            'userId' => $userId
        ]);
    }

    /**
     * Contacts list page
     */
    #[Route('/contacts', name: 'page_contacts', methods: ['GET'])]
    public function contacts(): Response
    {
        return $this->render('contacts/list.html.twig');
    }
}


