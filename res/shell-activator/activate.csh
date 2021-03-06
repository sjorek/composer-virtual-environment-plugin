#!@SHEBANG@
# This file must be used with "source @BIN_PATH@/activate.csh" *from csh*.
# You cannot run it directly.
# Created by Davide Di Blasi <davidedb@gmail.com>.
# Ported to Python 3.3 venv by Andrew Svetlov <andrew.svetlov@gmail.com>
# Adapted for composer by Stephan Jorek <stephan.jorek@gmail.com>

if ("$?COMPOSER_VENV") then
    goto skip 
endif

alias deactivate '\
if (! "$?nondestructive") then \
    foreach composer_venv_hook_file ( "@SHELL_HOOK_DIR@/pre-deactivate.d"/*.{csh,sh} /dev/nul[l] ) \
       source "$composer_venv_hook_file" \
    end\
    unset composer_venv_hook_file \
endif \
if ( "$?_OLD_COMPOSER_VENV_PATH" != 0 ) then \
    setenv PATH "$_OLD_COMPOSER_VENV_PATH" \
    unset _OLD_COMPOSER_VENV_PATH \
endif \
rehash \
if ( "$?_OLD_COMPOSER_VENV_PROMPT" != 0 ) then \
    set prompt="$_OLD_COMPOSER_VENV_PROMPT" \
    unset _OLD_COMPOSER_VENV_PROMPT \
endif \
unsetenv COMPOSER_VENV \
unsetenv COMPOSER_VENV_DIR \
if (! "$?nondestructive") then \
    unalias deactivate \
    echo "" \
    echo "Left virtual composer shell environment." \
    echo "" \
    echo "Good Bye!" \
    echo "" \
    foreach composer_venv_hook_file ( "@SHELL_HOOK_DIR@/post-deactivate.d"/*.{csh,sh} /dev/nul[l] ) \
        source "$composer_venv_hook_file" \
    end\
    unset composer_venv_hook_file \
    rehash \
endif \
'

# Unset irrelevant variables.
set nondestructive=1
deactivate
unset nondestructive

foreach composer_venv_hook_file ( "@SHELL_HOOK_DIR@/pre-activate.d"/*.{csh,sh} /dev/nul[l] )
    source "$composer_venv_hook_file"
end
unset composer_venv_hook_file

setenv COMPOSER_VENV "@NAME@"
setenv COMPOSER_VENV_DIR "@BASE_DIR@"

set _OLD_COMPOSER_VENV_PATH="$PATH"
setenv PATH "@BIN_DIR@:$PATH"


set _OLD_COMPOSER_VENV_PROMPT="$prompt"

if (! "$?COMPOSER_VENV_DISABLE_PROMPT") then
    if ("$COMPOSER_VENV" != "") then
        set env_name = "$COMPOSER_VENV"
    else
        if (`basename "$COMPOSER_VENV_DIR"` == "__") then
            # special case for Aspen magic directories
            # see http://www.zetadev.com/software/aspen/
            set env_name = `basename \`dirname "$COMPOSER_VENV_DIR"\``
        else
            set env_name = `basename "$COMPOSER_VENV_DIR"`
        endif
    endif
    set prompt = "[$env_name] $prompt"
    unset env_name
endif

rehash

echo ""
echo "virtual composer shell environment"
echo ""
echo "    Name: $COMPOSER_VENV"
echo "    Path: $COMPOSER_VENV_DIR"
echo ""
echo "Run 'deactivate' to exit the environment and return to normal shell."
echo ""

foreach composer_venv_hook_file ( "@SHELL_HOOK_DIR@/post-activate.d"/*.{csh,sh} /dev/nul[l] )
    source "$composer_venv_hook_file"
end
unset composer_venv_hook_file

rehash

goto done


skip:
    echo ""
    echo "A virtual composer shell environment is already active!"
    echo ""
    echo "    Name: $COMPOSER_VENV"
    echo "    Path: $COMPOSER_VENV_DIR"
    echo ""
    echo "Run 'deactivate' before activating this environment."
    echo ""
    goto done

done:
    # nothing to do here
