#  chess-pieces-design-viewer
![chess-pieces-design-viewer preview](/chess-piece-design-viewer-preview-image.png)
A simple viewer for your Chess piece designs.
Fan of the awesome [ShareChess](https://sharechess.github.io/), but loving the PHP dynamics -and not wanting to have to precompile a whole project to update an item in a list-, I built my own viewer to be able to see my Chess pieces design iterations on the board.

##  Requirements
- It's a PHP project, so a server
- FTP access to upload your piece sets to the /pieces directory

##  Features
1. List and view all piece sets uploaded to the pieces directory via FTP
2. Refresh the list manually with a button, and cache it after uploading new sets
3. Renaming of pieces. If the pieces you upload don't follow the bB.png/svg/webp lichess standard (where b is "black" and B is Bishop and so on) it will offer a button to rename them and try its best to do so and load the corrected piece set.
4. Flip the board
5. Link with a clean URL to your favorite piece set so you can share them with people

##  Thanks to
- DeepSeek, who offered amazing help with this project.
- [ShareChess](https://sharechess.github.io/) who offered massive inspiration ---> https://github.com/sharechess/sharechess.github.io

##  Project wishlist: 
An upload button, a rename all button, customizable/choosable board colors, download button, favorite saving, game embedding functionality as in ShareChess... none of them happening soon! But be my guest!
