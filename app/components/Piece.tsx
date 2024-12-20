interface PieceProps {
    piece: string | null
}
export function Piece({ piece }: PieceProps) {
    if (!piece) {
        return null;
    }

    let pieceStyle = {};
    if (piece === piece.toUpperCase()) {
        pieceStyle = {
            color: 'YellowGreen'
        }
    } else {
        pieceStyle = {
            color: 'black'
        }
    }


    return <span style={pieceStyle} >{
        piece === 'p' ? '♙' :
            piece === 'r' ? '♖' :
                piece === 'n' ? '♘' :
                    piece === 'b' ? '♗' :
                        piece === 'q' ? '♕' :
                            piece === 'k' ? '♔' :
                                piece === 'P' ? '♙' :
                                    piece === 'R' ? '♖' :
                                        piece === 'N' ? '♘' :
                                            piece === 'B' ? '♗' :
                                                piece === 'Q' ? '♕' :
                                                    piece === 'K' ? '♔' : ''
    }</span>
}