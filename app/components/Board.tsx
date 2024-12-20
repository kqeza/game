'use client'
import React, { useState, useEffect } from 'react';
import { Square } from './Square';

const initialBoard = [
    ['r', 'n', 'b', 'q', 'k', 'b', 'n', 'r'],
    ['p', 'p', 'p', 'p', 'p', 'p', 'p', 'p'],
    [null, null, null, null, null, null, null, null],
    [null, null, null, null, null, null, null, null],
    [null, null, null, null, null, null, null, null],
    [null, null, null, null, null, null, null, null],
    ['P', 'P', 'P', 'P', 'P', 'P', 'P', 'P'],
    ['R', 'N', 'B', 'Q', 'K', 'B', 'N', 'R'],
];

export function Board() {
    const [boardState, setBoardState] = useState(initialBoard);
    const [selectedSquare, setSelectedSquare] = useState<null | { row: number, col: number }>(null);
    const [possibleMoves, setPossibleMoves] = useState<{ row: number, col: number }[]>([]);
    const [currentPlayer, setCurrentPlayer] = useState('user');
    const [isCheckMate, setIsCheckMate] = useState(false);
    const [isStaleMate, setIsStaleMate] = useState(false);
    const [castling, setCastling] = useState({
        white: {
            kingside: true,
            queenside: true,
        },
        black: {
            kingside: true,
            queenside: true,
        }
    });

    const handleClick = async (row: number, col: number) => {
        if (currentPlayer === 'bot' || isCheckMate || isStaleMate) {
            return;
        }

        if (selectedSquare === null) {
            // Если клетка не выбрана, проверяем, есть ли на ней фигура текущего игрока
            if (boardState[row][col] &&
                ((currentPlayer === 'user' && boardState[row][col] === boardState[row][col].toUpperCase()) ||
                    (currentPlayer === 'bot' && boardState[row][col] === boardState[row][col].toLowerCase()))
            ) {
                // Если фигура текущего игрока есть, запоминаем ее координаты и запрашиваем возможные ходы
                setSelectedSquare({ row, col });
                const possibleMovesResponse = await fetch('/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ board: boardState, from: { row, col }, castling: castling, currentPlayer: currentPlayer }),
                });
                const possibleMovesData = await possibleMovesResponse.json();
                if (possibleMovesData.error) {
                    throw new Error(possibleMovesData.error);
                }

                setPossibleMoves(possibleMovesData.possibleMoves || []);
            }
        } else {
            handleMove(row, col);
            setSelectedSquare(null);
            setPossibleMoves([]);
        }
    };

    // Функция для превращения пешки
    const handlePawnPromotion = async (newBoard: any, move: any, promotedPiece: any) => {
        if (move.to.row === 0 || move.to.row === 7) {

            if ((move.to.row === 0 && newBoard[move.to.row][move.to.col] === 'P') || (move.to.row === 7 && newBoard[move.to.row][move.to.col] === 'p')) {

                newBoard[move.to.row][move.to.col] = promotedPiece;
            }

        }

        return newBoard;
    }

    // Функция для обработки хода
    const handleMove = async (row: number, col: number) => {
        if (selectedSquare === null) {
            return;
        }

        // Создаем объект хода для отправки на сервер
        let move = { from: selectedSquare, to: { row, col }, board: boardState, castling: castling, currentPlayer: currentPlayer };
        try {
            // Отправляем запрос на сервер
            const response = await fetch('/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(move),
            });
            const data = await response.json();
            if (data.error) {
                throw new Error(data.error);
            }


            let updatedBoardState = await handlePawnPromotion(data.board, move, 'Q');
            setBoardState(updatedBoardState)
            setCastling(data.castling);

            if (data.botMove) {
                let updatedBoardStateBot = await handlePawnPromotion(data.board, data.botMove, 'q');
                setBoardState(updatedBoardStateBot);
            }
            if (data.isCheckMate) {
                setIsCheckMate(true);
            } else if (data.isStaleMate) {
                setIsStaleMate(true);
            }

            if (currentPlayer === 'user') {
                setCurrentPlayer('bot')
            } else {
                setCurrentPlayer('user')
            }

        } catch (error) {
            console.error('Error:', error);
            alert(error);
        }
    };

    const getSquareClassName = (row: number, col: number): boolean => {
        if (selectedSquare && row === selectedSquare.row && col === selectedSquare.col) {
            return true;
        }
        if (possibleMoves.some(move => move.row === row && move.col === col)) {
            return true;
        }
        return false;
    };


    return (
        <div className="board relative w-full h-full flex flex-col border-2 border-gray-700 shadow-md">
            {boardState.map((row, rowIndex) => (
                <div key={rowIndex} className="row flex w-full h-[calc(100%/8)]">
                    {row.map((piece, colIndex) => (
                        <Square
                            key={colIndex}
                            row={rowIndex}
                            col={colIndex}
                            piece={piece}
                            onClick={() => handleClick(rowIndex, colIndex)}
                            isHighlighted={getSquareClassName(rowIndex, colIndex)}
                        />
                    ))}
                </div>
            ))}
            {/* Оверлей при мате */}
            {isCheckMate && <div className="overlay absolute top-0 left-0 w-full h-full bg-black/50 flex justify-center items-center">
                <div className="message bg-white p-5 rounded-md">
                    <h1 className="text-4xl"> Check Mate!</h1>
                    <button className="bg-gray-800 text-white rounded-md p-2 cursor-pointer" onClick={() => window.location.reload()}>Restart</button>
                </div>
            </div>}
            {/* Оверлей при пате */}
            {isStaleMate && <div className="overlay absolute top-0 left-0 w-full h-full bg-black/50 flex justify-center items-center">
                <div className="message bg-white p-5 rounded-md">
                    <h1 className="text-4xl">Stale Mate!</h1>
                    <button className="bg-gray-800 text-white rounded-md p-2 cursor-pointer" onClick={() => window.location.reload()}>Restart</button>
                </div>
            </div>}
        </div>
    );
}