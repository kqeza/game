import { Piece } from './Piece';

interface SquareProps {
    row: number;
    col: number;
    piece: string | null;
    onClick: (row: number, col: number) => void;
    isHighlighted: boolean;
}

export function Square({ row, col, piece, onClick, isHighlighted }: SquareProps) {
    const squareClass = `square relative h-12 w-12 flex justify-center items-center ${(row + col) % 2 === 0 ? 'bg-gray-100' : 'bg-gray-200'
        } ${isHighlighted ? 'bg-yellow-300/40' : ''} hover:bg-gray-300`;

    return (
        <div className={squareClass} onClick={() => onClick(row, col)}>
            {piece && <Piece piece={piece} />}
        </div>
    );
}