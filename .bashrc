# .bashrc

# Source global definitions
if [ -f /etc/bashrc ]; then
	. /etc/bashrc
fi

# User specific environment
PATH="$HOME/.local/bin:$HOME/bin:$PATH"
export PATH

# Uncomment the following line if you don't like systemctl's auto-paging feature:
# export SYSTEMD_PAGER=

# User specific aliases and functions

alias ls='ls -ohp --group-directories-first --time-style=long-iso'
PS1='\n\[\e[0;33m\]\t \[\e[1;32m\]\h \[\e[0;31m\]\w\[\e[0m\]\n\$ '
PROMPT_COMMAND='echo -ne "\033]0;${HOSTNAME}\007"'
