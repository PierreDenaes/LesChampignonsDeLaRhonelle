<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FlashMessageController extends AbstractController
{
    #[Route('/add-flash-message', name: 'add_flash_message', methods: ['POST'])]
    public function addFlashMessage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['message']) && isset($data['type'])) {
            // Ajouter le message flash
            $this->addFlash($data['type'], $data['message']);
            
            // Retourner le message flash dans la réponse JSON
            return new JsonResponse([
                'success' => true, 
                'message' => $data['message'],
                'type' => $data['type']
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
    }
}