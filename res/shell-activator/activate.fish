#!@SHEBANG@
# This file must be used with ". @BIN_PATH@/activate.fish" *from fish* (http://fishshell.org)
# you cannot run it directly

if test -n "$COMPOSER_VENV"
    echo ""
    echo "A virtual composer shell environment is already active!"
    echo ""
    echo "    Name: $COMPOSER_VENV"
    echo "    Path: $COMPOSER_VENV_DIR"
    echo ""
    echo "Run 'deactivate' before activating this environment."
    echo ""
else

    function deactivate  -d "Exit virtual environment and return to normal shell environment"

        if test "$argv[1]" != "nondestructive"
            for f in ( find "@SHELL_HOOK_DIR@/pre-deactivate.d" -maxdepth 1 -type f \( -name "*.sh" -or -name "*.fish" \) 2>/dev/null | sort )
                source "$f"
            end
        end

                # reset old environment variables
        if test -n "$_OLD_COMPOSER_VENV_PATH"
            set -gx PATH $_OLD_COMPOSER_VENV_PATH
            set -e _OLD_COMPOSER_VENV_PATH
        end
    
        if test -n "$_OLD_FISH_PROMPT_OVERRIDE"
            functions -e fish_prompt
            set -e _OLD_FISH_PROMPT_OVERRIDE
            functions -c _old_fish_prompt fish_prompt
            functions -e _old_fish_prompt
        end
    
        set -e COMPOSER_VENV
        set -e COMPOSER_VENV_DIR
        if test "$argv[1]" != "nondestructive"
            # Self destruct!
            functions -e deactivate

            echo ""
            echo "Left virtual composer shell environment."
            echo ""
            echo "Good Bye!"
            echo ""

            for f in ( find "@SHELL_HOOK_DIR@/post-deactivate.d" -maxdepth 1 -type f \( -name "*.sh" -or -name "*.fish" \) 2>/dev/null | sort )
                source "$f"
            end
        end
    end

    # unset irrelevant variables
    deactivate nondestructive

    for f in ( find "@SHELL_HOOK_DIR@/pre-activate.d" -maxdepth 1 -type f \( -name "*.sh" -or -name "*.fish" \) 2>/dev/null | sort )
        source "$f"
    end

    set -gx COMPOSER_VENV "@NAME@"
    set -gx COMPOSER_VENV_DIR "@BASE_DIR@"

    set -gx _OLD_COMPOSER_VENV_PATH $PATH
    set -gx PATH "@BIN_DIR@" $PATH

    if test -z "$COMPOSER_VENV_DISABLE_PROMPT"
        # fish uses a function instead of an env var to generate the prompt.

        # save the current fish_prompt function as the function _old_fish_prompt
        functions -c fish_prompt _old_fish_prompt

        # with the original prompt function renamed, we can override with our own.
        function fish_prompt
            # Save the return status of the last command
            set -l old_status $status

            # Prompt override?
            if test -n "$COMPOSER_VENV"
                printf "%s%s" "(@NAME@) " (set_color normal)
            else
                # ...Otherwise, prepend env
                set -l _checkbase (basename "$COMPOSER_VENV_DIR")
                if test $_checkbase = "__"
                    # special case for Aspen magic directories
                    # see http://www.zetadev.com/software/aspen/
                    printf "%s[%s]%s " (set_color -b blue white) (basename (dirname "$COMPOSER_VENV_DIR")) (set_color normal)
                else
                    printf "%s(%s)%s" (set_color -b blue white) (basename "$COMPOSER_VENV_DIR") (set_color normal)
                end
            end
    
            # Restore the return status of the previous command.
            echo "exit $old_status" | .
            _old_fish_prompt
        end

        set -gx _OLD_FISH_PROMPT_OVERRIDE "$COMPOSER_VENV_DIR"
    end

    echo ""
    echo "virtual composer shell environment"
    echo ""
    echo "    Name: $COMPOSER_VENV"
    echo "    Path: $COMPOSER_VENV_DIR"
    echo ""
    echo "Run 'deactivate' to exit the environment and return to normal shell."
    echo ""

    for f in ( find "@SHELL_HOOK_DIR@/post-activate.d" -maxdepth 1 -type f \( -name "*.sh" -or -name "*.fish" \) 2>/dev/null | sort )
        source "$f"
    end

end