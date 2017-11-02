if (Test-Path env:COMPOSER_VENV) {

    Write-Host ""
    Write-Host "A virtual composer shell environment is already active!"
    Write-Host ""
    Write-Host "    Name: $env:COMPOSER_VENV"
    Write-Host "    Path: $env:COMPOSER_VENV_DIR"
    Write-Host ""
    Write-Host "Run 'deactivate' before activating this environment."
    Write-Host ""

} else {

    function global:deactivate ([switch]$NonDestructive) {
        if (!$NonDestructive) {
            foreach($hook in @("@SHELL_HOOK_DIR@\pre-deactivate.d\*.ps1")) {
                . $hook
            }
        }

        # Revert to original values
        if (Test-Path function:_OLD_COMPOSER_VENV_PROMPT) {
            copy-item function:_OLD_COMPOSER_VENV_PROMPT function:prompt
            remove-item function:_OLD_COMPOSER_VENV_PROMPT
        }

        if (Test-Path env:_OLD_COMPOSER_VENV_PATH) {
            copy-item env:_OLD_COMPOSER_VENV_PATH env:PATH
            remove-item env:_OLD_COMPOSER_VENV_PATH
        }

        if (Test-Path env:COMPOSER_VENV) {
            remove-item env:COMPOSER_VENV
        }

        if (Test-Path env:COMPOSER_VENV_DIR) {
            remove-item env:COMPOSER_VENV_DIR
        }

        if (!$NonDestructive) {
            foreach($hook in @("@SHELL_HOOK_DIR@\post-deactivate.d\*.ps1")) {
                . $hook
            }

            # Self destruct!
            remove-item function:deactivate
        }
    }

    deactivate -nondestructive

    foreach($hook in @("@SHELL_HOOK_DIR@\pre-activate.d\*.ps1")) {
        . $hook
    }

    $env:COMPOSER_VENV="@NAME@"
    $env:COMPOSER_VENV_DIR="@BASE_DIR@"

    if (! $env:COMPOSER_VENV_DISABLE_PROMPT) {
        # Set the prompt to include the env name
        # Make sure _OLD_COMPOSER_VENV_PROMPT is global
        function global:_OLD_COMPOSER_VENV_PROMPT {""}
        copy-item function:prompt function:_OLD_COMPOSER_VENV_PROMPT
        function global:prompt {
            Write-Host -NoNewline -ForegroundColor Green '@NAME@'
            _OLD_COMPOSER_VENV_PROMPT
        }
    }

    # Add the venv to the PATH
    copy-item env:PATH env:_OLD_COMPOSER_VENV_PATH
    $env:PATH = "@BIN_DIR@;$env:PATH"

    Write-Host ""
    Write-Host "virtual composer shell environment"
    Write-Host ""
    Write-Host "    Name: $env:COMPOSER_VENV"
    Write-Host "    Path: $env:COMPOSER_VENV_DIR"
    Write-Host ""
    Write-Host "Run 'deactivate' to exit the environment."
    Write-Host ""

    foreach($hook in @("@SHELL_HOOK_DIR@\post-activate.d\*.ps1")) {
        . $hook
    }

}