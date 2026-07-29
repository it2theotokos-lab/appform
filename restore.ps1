$shell = New-Object -ComObject Shell.Application
$recycleBin = $shell.Namespace(0xA)
foreach ($item in $recycleBin.Items()) {
    if ($item.Name -match 'upload_release.php' -or $item.Name -match 'check_release.php') {
        Write-Output "Restoring $($item.Name) from $($item.Path)"
        # Try to restore using verb
        $item.InvokeVerb("undelete")
    }
}
