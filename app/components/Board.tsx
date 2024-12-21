'use client'
import React, { useState, useEffect } from 'react';
import { Square } from './Square';
import axios from 'axios';

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
    const [boardState, setBoardState] = useState<any[][] | undefined>(undefined);
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

    useEffect(() => {
        const fetchInitialBoard = async () => {
            try {
                const response = await axios({
                    method: 'post',
                    url: 'http://127.0.0.1:8000/chess',
                    data: {},
                });

                const data = response.data;
                console.log('fetchInitialBoard', data);
                if (data.error) {
                    throw new Error(data.error);
                }
                setBoardState(data.board);
                setCastling(data.castling);
            } catch (error) {
                console.error('Error fetching initial board:', error);
                alert(error);
            }
        };

        fetchInitialBoard();
    }, []);

    const handleClick = async (row: number, col: number) => {
        console.log('click', row, col);
        if (currentPlayer === 'bot' || isCheckMate || isStaleMate) {
            return;
        }

        if (selectedSquare === null) {
            if (boardState && boardState[row][col] &&
                ((currentPlayer === 'user' && boardState[row][col] === boardState[row][col].toUpperCase()) ||
                    (currentPlayer === 'bot' && boardState[row][col] === boardState[row][col].toLowerCase()))

            ) {
                console.log('Selected', row, col);
                setSelectedSquare({ row, col });

                try {
                    const possibleMovesResponse = await axios({
                        method: 'post',
                        url: 'http://127.0.0.1:8000/chess',
                        data:
                        {
                            board: boardState,
                            from: { row: parseInt(row.toString()), col: parseInt(col.toString()) },
                            castling: castling,
                            currentPlayer: currentPlayer
                        },
                    });

                    const possibleMovesData = possibleMovesResponse.data;
                    setPossibleMoves(possibleMovesData.possibleMoves || []);
                }
                catch (error) {
                    console.log(error);
                    alert(error);
                }

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
        console.log('Board state', boardState);

        let move = { from: selectedSquare, to: { row, col }, board: boardState, castling: castling, currentPlayer: currentPlayer };
        try {
            const response = await axios({
                method: 'post',
                url: 'http://127.0.0.1:8000/chess',
                data: move
            });
            const data = response.data;
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
            console.log('Board state', boardState);


        }
        catch (error) {
            console.log(error);
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
            {boardState && boardState.map((row, rowIndex) => (
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