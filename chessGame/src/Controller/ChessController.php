<?php

namespace App\Controller;

use App\Entity\Board;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;


class ChessController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
     #[Route("/chess", name:"chess", methods:["POST"])]
    public function chess(Request $request, SerializerInterface $serializer): JsonResponse
    {
           try {
            $data = json_decode($request->getContent(), true);
            $board = new Board();
             if (isset($data['board'])) {
                 $board->setBoard($data['board']);
             }

             if (isset($data['castling'])) {
                 $board->setCastling($data['castling']);
             }
            if (isset($data['currentPlayer'])) {
                 $board->setCurrentPlayer($data['currentPlayer']);
             }

             if (isset($data['from']) && isset($data['to'])) {
                 $board->move($data);

                $botMove = null;
                $isCheckMate = false;
                $isStaleMate = false;
                if ($board->getCurrentPlayer() === 'black') {
                    $botMove = $board->getBotMove();

                    if ($botMove) {
                        $board->move($botMove);

                    }

                    $isCheckMate = $board->isCheckMate('white');
                     $isStaleMate = $board->isStaleMate('white');
                 } else {
                    $isCheckMate = $board->isCheckMate('black');
                     $isStaleMate = $board->isStaleMate('black');
                 }



                return new JsonResponse([
                    'board' => $board->getBoard(),
                    'castling' => $board->getCastling(),
                     'botMove' => $botMove,
                    'isCheckMate' => $isCheckMate,
                    'isStaleMate' => $isStaleMate
                ]);
             } else if (isset($data['from'])) {
                 $possibleMoves = $board->getPossibleMovesPHP($data['from'], $board->getCurrentPlayer());
                  return new JsonResponse([
                      'possibleMoves' => $possibleMoves,
                  ]);
             } else {
                return new JsonResponse([
                 'board' => $board->getBoard(),
                   'castling' => $board->getCastling(),
                 ]);
            }
        } catch (\Throwable $e) {
             return new JsonResponse([
                'error' => 'Server error: ' . $e->getMessage(),
                 'file' => $e->getFile(),
                  'line' => $e->getLine(),
              ], Response::HTTP_INTERNAL_SERVER_ERROR);

         }
    }
}