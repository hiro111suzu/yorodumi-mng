
PS1='\n\[\e[0;33m\]\t \[\e[1;32m\]\h \[\e[0;31m\]\w\[\e[0m\]\n\$ '
alias ls='ls -ohp --group-directories-first --time-style=long-iso'
PROMPT_COMMAND='echo -ne "\033]0;${HOSTNAME}\007"'
echo "prof"
