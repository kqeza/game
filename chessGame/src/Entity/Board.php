<?php

namespace App\Entity;

use App\Repository\BoardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoardRepository::class)]

class Board
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $board = [];

    #[ORM\Column(length: 255)]
    private ?string $currentPlayer = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $castling = [];

    public function __construct()
    {
        $this->board = $this->createInitialBoard();
        $this->currentPlayer = 'white'; // Начало с белых
        $this->castling = [ // Массив, отслеживающий возможность рокировки для каждой стороны
            'white' => ['kingside' => true, 'queenside' => true], 
            'black' => ['kingside' => true, 'queenside' => true], 
        ];
    }
    private function createInitialBoard(): array
    {
        $board = []; 
        for ($row = 0; $row < 8; $row++) { 
            $board[$row] = [];
            for ($col = 0; $col < 8; $col++) { 
                $board[$row][$col] = null; 
            }
        }
       
        $board[0] = ['r', 'n', 'b', 'q', 'k', 'b', 'n', 'r'];
        $board[1] = ['p', 'p', 'p', 'p', 'p', 'p', 'p', 'p']; 
        $board[6] = ['P', 'P', 'P', 'P', 'P', 'P', 'P', 'P']; 
        $board[7] = ['R', 'N', 'B', 'Q', 'K', 'B', 'N', 'R']; 
        return $board;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBoard(): ?array
    {
        return $this->board;
    }

    public function setBoard(array $board): self
    {
        $this->board = $board;

        return $this;
    }

     public function getCurrentPlayer(): ?string
    {
        return $this->currentPlayer;
    }

    public function setCurrentPlayer(string $currentPlayer): self
    {
        $this->currentPlayer = $currentPlayer;

        return $this;
    }
     public function getCastling(): ?array
    {
        return $this->castling;
    }

    public function setCastling(array $castling): self
    {
        $this->castling = $castling;

        return $this;
    }


      public function updateCastling(array $move): void
    {
        $fromRow = $move['from']['row'];
        $fromCol = $move['from']['col'];
        $piece = $this->board[$fromRow][$fromCol];


        if ($piece === 'K') {
            $this->castling['white']['kingside'] = false;
            $this->castling['white']['queenside'] = false;
        } elseif ($piece === 'k') {
            $this->castling['black']['kingside'] = false;
            $this->castling['black']['queenside'] = false;
        } elseif ($piece === 'R' && $fromRow === 7 && $fromCol === 7) {
            $this->castling['white']['kingside'] = false;
        } elseif ($piece === 'R' && $fromRow === 7 && $fromCol === 0) {
            $this->castling['white']['queenside'] = false;
        } elseif ($piece === 'r' && $fromRow === 0 && $fromCol === 7) {
            $this->castling['black']['kingside'] = false;
        } elseif ($piece === 'r' && $fromRow === 0 && $fromCol === 0) {
            $this->castling['black']['queenside'] = false;
        }
    }

   public function move(array $move): array
    {      
        dump('move called', $move);
        $fromRow = (int) $move['from']['row'];
        $fromCol = (int) $move['from']['col'];
        $toRow = (int) $move['to']['row'];
        $toCol = (int) $move['to']['col'];
        $piece = $this->board[$fromRow][$fromCol];

        dump('move', $fromRow, $fromCol, $toRow, $toCol);


        if(!is_int($fromRow) || !is_int($fromCol) || !is_int($toRow) || !is_int($toCol)){
            dump('Not a number', $fromRow, $fromCol, $toRow, $toCol);
            throw new \Exception("Invalid move: Invalid from or to coordinates, incorrect type");
        }

        if (!isset($this->board[$fromRow][$fromCol]) || !isset($this->board[$toRow][$toCol])) {
            dump('Invalid Coordinates', $fromRow, $fromCol, $toRow, $toCol, $this->board);
          throw new \Exception("Invalid move: Invalid from or to coordinates");
        } 
        $piece = $this->board[$fromRow][$fromCol];
        dump('piece', $piece);
          $this->updateCastling($move);

          if (!$this->isValidMove($move)) {
            throw new \Exception("Invalid move: Not a valid move for the piece");
        }
        dump('piece', $piece);
        //Рокировка
        if (isset($move['castling'])){
            if ($move['castling'] === 'whiteKingside') {
                $this->board[$toRow][$toCol] = $this->board[$fromRow][$fromCol];
                $this->board[$fromRow][$fromCol] = null;

                $this->board[$toRow][$toCol - 1] = 'R';
                $this->board[$toRow][7] = null;

            } else if ($move['castling'] === 'whiteQueenside') {
                $this->board[$toRow][$toCol] = $this->board[$fromRow][$fromCol];
                $this->board[$fromRow][$fromCol] = null;
                $this->board[$toRow][$toCol + 1] = 'R';
                $this->board[$toRow][0] = null;

            } else if ($move['castling'] === 'blackKingside') {
                $this->board[$toRow][$toCol] = $this->board[$fromRow][$fromCol];
                $this->board[$fromRow][$fromCol] = null;
                $this->board[$toRow][$toCol - 1] = 'r';
                $this->board[$toRow][7] = null;


            } else if ($move['castling'] === 'blackQueenside') {
                $this->board[$toRow][$toCol] = $this->board[$fromRow][$fromCol];
                $this->board[$fromRow][$fromCol] = null;
                $this->board[$toRow][$toCol + 1] = 'r';
                $this->board[$toRow][0] = null;


            }
        } else {
             $this->board[$toRow][$toCol] = $this->board[$fromRow][$fromCol];
            $this->board[$fromRow][$fromCol] = null;
        }

        $this->currentPlayer = $this->currentPlayer === 'white' ? 'black' : 'white'; // Меняем игрока

        return $this->board;

    }


    public function isCheckMate(string $currentPlayer): bool
    {
        $kingPos = null;
        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if (($currentPlayer === 'white' && $this->board[$row][$col] === 'K') || ($currentPlayer === 'black' && $this->board[$row][$col] === 'k')) {
                    $kingPos = ['row' => $row, 'col' => $col];
                    break;
                }
            }
        }

        if ($kingPos === null) {
            return false; // Короля нет на доске - ошибка;
        }


        $enemyColor = $currentPlayer === 'white' ? 'black' : 'white';


        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if (($enemyColor === 'white' && $this->board[$row][$col] && strtoupper($this->board[$row][$col]) === $this->board[$row][$col]) ||
                    ($enemyColor === 'black' && $this->board[$row][$col] && strtolower($this->board[$row][$col]) === $this->board[$row][$col])) {
                    $possibleEnemyMoves = $this->getPossibleMovesPHP(['row' => $row, 'col' => $col], $enemyColor);
                    if (!empty($possibleEnemyMoves)) {
                        foreach ($possibleEnemyMoves as $move) {
                            if ($move['row'] === $kingPos['row'] && $move['col'] === $kingPos['col']) {

                                $escapeMoves = $this->getPossibleMovesPHP($kingPos, $currentPlayer);
                                if (empty($escapeMoves)) {
                                    return true;
                                }

                            }
                        }

                    }


                }
            }
        }
        return false;
    }

    public function isStaleMate(string $currentPlayer): bool
    {
        $kingPos = null;
        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if (($currentPlayer === 'white' && $this->board[$row][$col] === 'K') || ($currentPlayer === 'black' && $this->board[$row][$col] === 'k')) {
                    $kingPos = ['row' => $row, 'col' => $col];
                    break;
                }
            }
        }
        if ($kingPos === null) return true; // Нет короля - пат

        $allMoves = false;
        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if (($currentPlayer === 'white' && $this->board[$row][$col] && strtoupper($this->board[$row][$col]) === $this->board[$row][$col]) ||
                    ($currentPlayer === 'black' && $this->board[$row][$col] && strtolower($this->board[$row][$col]) === $this->board[$row][$col])) {
                    $possibleMoves = $this->getPossibleMovesPHP(['row' => $row, 'col' => $col], $currentPlayer);
                    if (!empty($possibleMoves)) {
                        $allMoves = true;
                    }
                }
            }
        }
        if (!$allMoves) {
            return true;
        }
        // Если есть куда ходить и не мат, то не пат
        if ($this->isCheckMate($currentPlayer)) {
            return false;
        }

        $possibleKingMoves = $this->getPossibleMovesPHP($kingPos, $currentPlayer);
        if (empty($possibleKingMoves)) {
            return true;
        }


        return false;
    }

    public function getBotMove(): ?array
    {

        $emptySquares = [];
        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if ($this->board[$row][$col] && preg_match('/[a-z]/', $this->board[$row][$col])) {
                    $emptySquares[] = ['row' => $row, 'col' => $col];
                }
            }
        }

        if (empty($emptySquares)) {
            return null; // Нет ходов
        }


        $selectedBotPiece = $emptySquares[array_rand($emptySquares)];
        $possibleBotMoves = $this->getPossibleMovesPHP($selectedBotPiece, 'black'); // Используем "black", предполагая, что бот всегда играет за черных


        if (empty($possibleBotMoves)) {
            return null; // Нет ходов
        }

        $selectedBotMove = $possibleBotMoves[array_rand($possibleBotMoves)];


        return ['from' => $selectedBotPiece, 'to' => $selectedBotMove];
    }

    private function isValidMove(array $move): bool
    {
        $fromRow = (int) $move['from']['row'];    
      $fromCol = (int) $move['from']['col'];
      $toRow = (int) $move['to']['row'];
      $toCol = (int) $move['to']['col'];
       $piece = $this->board[$fromRow][$fromCol];


       dump('isValidMove',$fromRow, $fromCol, $toRow, $toCol,$piece);
        if (!$piece) {
            dump('No piece', $fromRow, $fromCol);
            return false; // Нет фигуры на стартовой клетке
        }
        $isWhite = ctype_upper($piece);

        if (($isWhite && $this->currentPlayer === 'black') || (!$isWhite && $this->currentPlayer === 'white')) {
            return false; // Ход не того игрока
        }

        $possibleMoves = $this->getPossibleMovesPHP(['row' => $fromRow, 'col' => $fromCol], $this->currentPlayer);
        dump('possibleMoves in is valid move', $possibleMoves);     
        foreach ($possibleMoves as $possibleMove) {
            if ($possibleMove['row'] === $toRow && $possibleMove['col'] === $toCol) {

                if (isset($move['castling'])) {
                    return $this->validateCastlingMove($move);
                }
                return true;
            }
        }


        return false;
    }

    private function validateCastlingMove(array $move): bool
    {
        $fromRow = $move['from']['row'];
        $fromCol = $move['from']['col'];
        $toRow = $move['to']['row'];
        $toCol = $move['to']['col'];

        $piece = $this->board[$fromRow][$fromCol];
        if ($piece === 'K' && $move['castling'] === 'whiteKingside') {
            if ($this->castling['white']['kingside'] &&
                empty($this->board[$fromRow][$fromCol + 1]) &&
                empty($this->board[$fromRow][$fromCol + 2]) &&
                $this->board[$fromRow][7] === 'R'
            ) {
                return true;
            }
        }
        if ($piece === 'K' && $move['castling'] === 'whiteQueenside') {
            if ($this->castling['white']['queenside'] &&
                empty($this->board[$fromRow][$fromCol - 1]) &&
                empty($this->board[$fromRow][$fromCol - 2]) &&
                empty($this->board[$fromRow][$fromCol - 3]) &&
                $this->board[$fromRow][0] === 'R'
            ) {
                return true;
            }
        }
        if ($piece === 'k' && $move['castling'] === 'blackKingside') {
            if ($this->castling['black']['kingside'] &&
                empty($this->board[$fromRow][$fromCol + 1]) &&
                empty($this->board[$fromRow][$fromCol + 2]) &&
                $this->board[$fromRow][7] === 'r'
            ) {
                return true;
            }
        }
        if ($piece === 'k' && $move['castling'] === 'blackQueenside') {
            if ($this->castling['black']['queenside'] &&
                empty($this->board[$fromRow][$fromCol - 1]) &&
                empty($this->board[$fromRow][$fromCol - 2]) &&
                empty($this->board[$fromRow][$fromCol - 3]) &&
                $this->board[$fromRow][0] === 'r'
            ) {
                return true;
            }
        }
        return false;

    }

   public function getPossibleMovesPHP(array $from, string $currentPlayer): array
    {       
        dump('getPossibleMovesPHP called', $from, $currentPlayer);
        $piece = $this->board[$from['row']][$from['col']];
        $moves = [];

        if (!$piece) {
            return $moves;
        }

        $isWhite = ctype_upper($piece);
        $direction = $isWhite ? -1 : 1;


        switch (strtolower($piece)) {
            case 'p': // Пешка
                $startRow = $isWhite ? 6 : 1;

                $movesPawn = function ($row, $col) use ($startRow, $direction, &$moves, $isWhite) {
                    $firstMove = $row === $startRow ? 2 * $direction : $direction;

                    $targetRow = $row + $firstMove;
                    $targetRowSingle = $row + $direction;


                    if ($targetRow >= 0 && $targetRow < 8 && empty($this->board[$targetRow][$col])) {
                        $moves[] = ['row' => $targetRow, 'col' => $col];
                        if ($row === $startRow && empty($this->board[$targetRowSingle][$col])) {
                            $moves[] = ['row' => $targetRowSingle, 'col' => $col];
                        }

                    }
                    $left = $col - 1;
                    $right = $col + 1;


                    if ($left >= 0 && $targetRowSingle >= 0 && $targetRowSingle < 8 && !empty($this->board[$targetRowSingle][$left]) &&
                        (($isWhite && strtolower($this->board[$targetRowSingle][$left]) === $this->board[$targetRowSingle][$left]) ||
                            (!$isWhite && strtoupper($this->board[$targetRowSingle][$left]) === $this->board[$targetRowSingle][$left]))) {
                        $moves[] = ['row' => $targetRowSingle, 'col' => $left];
                    }
                    if ($right < 8 && $targetRowSingle >= 0 && $targetRowSingle < 8 && !empty($this->board[$targetRowSingle][$right]) &&
                        (($isWhite && strtolower($this->board[$targetRowSingle][$right]) === $this->board[$targetRowSingle][$right]) ||
                            (!$isWhite && strtoupper($this->board[$targetRowSingle][$right]) === $this->board[$targetRowSingle][$right]))) {
                        $moves[] = ['row' => $targetRowSingle, 'col' => $right];
                    }
                };

                $movesPawn($from['row'], $from['col']);
                break;

            case 'r': // Ладья
            case 'b': // Слон
            case 'q': // Ферзь
                $directions = [];
                if (strtolower($piece) === 'r' || strtolower($piece) === 'q') {
                    $directions[] = [0, 1];
                    $directions[] = [0, -1];
                    $directions[] = [1, 0];
                    $directions[] = [-1, 0];
                }
                if (strtolower($piece) === 'b' || strtolower($piece) === 'q') {
                    $directions[] = [1, 1];
                    $directions[] = [1, -1];
                    $directions[] = [-1, 1];
                    $directions[] = [-1, -1];
                }


                foreach ($directions as $dir) {

                    $row = $from['row'] + $dir[0];
                    $col = $from['col'] + $dir[1];
                    while ($row >= 0 && $row < 8 && $col >= 0 && $col < 8) {
                        if (!empty($this->board[$row][$col])) {
                            if (($isWhite && strtolower($this->board[$row][$col]) === $this->board[$row][$col]) ||
                                (!$isWhite && strtoupper($this->board[$row][$col]) === $this->board[$row][$col])
                            ) {
                                $moves[] = ['row' => $row, 'col' => $col];
                            }

                            break;
                        }

                        $moves[] = ['row' => $row, 'col' => $col];
                        $row += $dir[0];
                        $col += $dir[1];
                    }
                }
                break;
            case 'n': // Конь
                $knightMoves = [
                    [-2, -1],
                    [-2, 1],
                    [-1, -2],
                    [-1, 2],
                    [1, -2],
                    [1, 2],
                    [2, -1],
                    [2, 1],
                ];
                foreach ($knightMoves as $m) {
                    $row = $from['row'] + $m[0];
                    $col = $from['col'] + $m[1];

                    if ($row >= 0 && $row < 8 && $col >= 0 && $col < 8 && (empty($this->board[$row][$col]) ||
                        ($isWhite && strtolower($this->board[$row][$col]) === $this->board[$row][$col]) ||
                        (!$isWhite && strtoupper($this->board[$row][$col]) === $this->board[$row][$col]))
                    ) {
                        $moves[] = ['row' => $row, 'col' => $col];
                    }
                }
                break;
            case 'k': // Король
                $kingMoves = [
                    [-1, -1], [-1, 0], [-1, 1],
                    [0, -1], [0, 1],
                    [1, -1], [1, 0], [1, 1],
                ];

                foreach ($kingMoves as $m) {
                    $row = $from['row'] + $m[0];
                    $col = $from['col'] + $m[1];
                    if ($row >= 0 && $row < 8 && $col >= 0 && $col < 8 && (empty($this->board[$row][$col]) ||
                        ($isWhite && strtolower($this->board[$row][$col]) === $this->board[$row][$col]) ||
                        (!$isWhite && strtoupper($this->board[$row][$col]) === $this->board[$row][$col]))
                    ) {
                        $moves[] = ['row' => $row, 'col' => $col];
                    }
                }
                //Рокировка
                if ($isWhite) {
                    if ($this->castling['white']['kingside'] &&
                        empty($this->board[$from['row']][$from['col'] + 1]) &&
                        empty($this->board[$from['row']][$from['col'] + 2]) &&
                        $this->board[$from['row']][7] === 'R'
                    ) {
                        $moves[] = ['row' => $from['row'], 'col' => $from['col'] + 2, 'castling' => 'whiteKingside'];
                    }
                    if ($this->castling['white']['queenside'] &&
                        empty($this->board[$from['row']][$from['col'] - 1]) &&
                        empty($this->board[$from['row']][$from['col'] - 2]) &&
                        empty($this->board[$from['row']][$from['col'] - 3]) &&
                        $this->board[$from['row']][0] === 'R'
                    ) {
                        $moves[] = ['row' => $from['row'], 'col' => $from['col'] - 2, 'castling' => 'whiteQueenside'];
                    }
                } else {
                    if ($this->castling['black']['kingside'] &&
                        empty($this->board[$from['row']][$from['col'] + 1]) &&
                        empty($this->board[$from['row']][$from['col'] + 2]) &&
                        $this->board[$from['row']][7] === 'r'
                    ) {
                        $moves[] = ['row' => $from['row'], 'col' => $from['col'] + 2, 'castling' => 'blackKingside'];
                    }
                    if ($this->castling['black']['queenside'] &&
                        empty($this->board[$from['row']][$from['col'] - 1]) &&
                        empty($this->board[$from['row']][$from['col'] - 2]) &&
                        empty($this->board[$from['row']][$from['col'] - 3]) &&
                        $this->board[$from['row']][0] === 'r'
                    ) {
                        $moves[] = ['row' => $from['row'], 'col' => $from['col'] - 2, 'castling' => 'blackQueenside'];
                    }
                }
                break;
            default:
                break;
        }
        dump('getPossibleMovesPHP returns', $moves);
        return $moves;
    }
}

// Обработка запроса