Izumi -- How to enable syntax highlighting for Izumi files in Vim
-----------------------------------------------------------------

To enable syntax highlighting for Izumi files in Vim, copy the
content of /usr/shared/izumi/highlighting in your ~/.vim folder.
You need to copy the following files, creating the directories
as needed:
	~/.vim/ftdetect/izu.vim
	~/.vim/syntax/izu.vim

You can do such by executing the following commands:

mkdir -p ~/.vim/ftdetect ~/.vim/syntax
cp  /usr/shared/izumi/highlighting/vim/ftdetect/izu.vim ~/.vim/ftdetect/.
cp  /usr/shared/izumi/highlighting/vim/syntax/izu.vim   ~/.vim/syntax/.


If you find any bug or have any improvement to suggest for
the highlighting, please feel free to send me your suggestions
(source diff preferred).

Ralf -- $Id$

