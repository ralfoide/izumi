" Vim syntax file
" Language:     Izumi
" Maintainer:   Ralf <webralf@alfray.com>
" URL:          http://izumi.alfray.com/
" Last Change:  2005 April 4

" For version 5.x: Clear all syntax items
" For version 6.x: Quit when a syntax file was already loaded
"if !exists("main_syntax")
"	if version < 600
"		syntax clear
"	elseif exists("b:current_syntax")
"		finish
"	endif
"	let main_syntax = 'izu'
"endif

"--------------
" Define syntax groups for Izumi

syn	case	match

syn region	izuTag		oneline		start="\["				end="\]"	contains=izuCmd,izuArg,izuLink,izuAnchor,izuDelim
syn	region	izuCmt					start="\[!--"			end="--\]"
syn	region	izuHeader				start="\[izu:header:--"	end="--\]"
syn	region	izuBold		oneline		start="__"hs=s+2		end="__"he=e-2
syn	region	izuItalics	oneline		start="''"hs=s+2		end="''"he=e-2

syn match	izuArg		contained	":[^:\]#]*"hs=s+1					contains=izuDate
syn	match	izuLink		contained	"|[^:\]#]*"hs=s+1
syn	match	izuAnchor	contained	"#[^\]]*"hs=s+1

syn	match	izuList					"^[ \t]*\* "

syn	match	izuDate		contained	"[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]"

syn	keyword	izuCmd		contained	a
syn	keyword	izuCmd		contained	izu
syn	keyword	izuCmd		contained	s

syn	keyword	izuDelim	contained	: \|


"--------------
" Now define highlighting for these groups

hi link		izuTag		Statement
hi link		izuCmd		Identifier
hi link		izuArg		Constant
hi link     izuLink     Constant
hi link		izuList		Structure
hi link		izuDelim	Delimiter
hi link		izuAnchor	Type
hi link		izuDate		Type

hi link		izuBold		Underlined
hi link		izuItalics	String

hi link		izuCmt		Comment
hi link		izuHeader	PreProc


"let b:current_syntax = "izu"
"
"if main_syntax == 'izu'
"  unlet main_syntax
"endif

"                 
