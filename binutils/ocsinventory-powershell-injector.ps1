<#
    Copyright 2005-2020 OCSInventory-NG/OCSInventory-server contributors.
    See the Contributors file for more details about them.

    This file is part of OCSInventory-NG/OCSInventory-server.

    OCSInventory-NG/OCSInventory-server is free software: you can redistribute
    it and/or modify it under the terms of the GNU General Public License as
    published by the Free Software Foundation, either version 2 of the License,
    or (at your option) any later version.

    OCSInventory-NG/OCSInventory-server is distributed in the hope that it
    will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
    of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with OCSInventory-NG/OCSInventory-server. if not, write to the
    Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
    MA 02110-1301, USA.
#>
param (
    [switch]$help = $false,
    [string]$directory,
    [string]$file,
    [string]$url,
    [string]$user,
    [string]$pass,
    [switch]$ssl,
    [switch]$remove,
    [switch]$info
)

<#
    .SYNOPSIS
        Display help
    .DESCRIPTION
        If -help is set in the command line, return the helper and quit
#>
function Get-InjectorHelper {
    Write-Output "
    DESCRIPTION:

    A command line tools to import .ocs files.
    USAGE:
    -help	        : this menu
    -directory	        : load every .ocs files from a directory
    -file	        : load a speficic file
    -url	        : ocsinventory backend URL
    -ssl                : enable SSL inventory injection
    -user               : Basic auth username
    -pass               : User password
    -remove	        : remove successfully injected files
    -info	        : verbose mode
    "
}

<#
    .SYNOPSIS
        Display debug log
    .DESCRIPTION
        Display debug log only if -info is sent
#>
function Write-InfoLog($str) {
    if($info.IsPresent){
        $currentTime = Get-Date -Format "MM/dd/yyyy HH:mm:ss"
        Write-Output "[OCSInject] $currentTime : $str"
    }
}

<#
    .SYNOPSIS
        Send file to OCS Server
    .DESCRIPTION
        Take the file, retrieve its content and send it to the OCS Inventory server
#>
function Send-File{

    Param(
        [parameter(Mandatory=$true)]
        [string] $filePath,
        [parameter(Mandatory=$true)]
        [hashtable] $headers
    )

    $fileContent = Get-Content($filePath)
    $fileName = Split-Path $filePath -leaf

    if($ssl.IsPresent -eq $true){
        add-type "
            using System.Net;
            using System.Security.Cryptography.X509Certificates;
            public class TrustAllCertsPolicy : ICertificatePolicy {
                public bool CheckValidationResult(
                    ServicePoint srvPoint, X509Certificate certificate,
                    WebRequest request, int certificateProblem) {
                    return true;
                }
            }
        "
        [System.Net.ServicePointManager]::CertificatePolicy = New-Object TrustAllCertsPolicy
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Ssl3, [Net.SecurityProtocolType]::Tls, [Net.SecurityProtocolType]::Tls11, [Net.SecurityProtocolType]::Tls12

        $webRequestStatus = Invoke-WebRequest `
        -Uri $url `
        -ContentType "application/xml" `
        -Method POST `
        -Body $fileContent `
        -UserAgent "OCS_POWERSHELL_INJECTOR_V2.8" `
        -Headers $headers
    }else{
        $webRequestStatus = Invoke-WebRequest `
        -Uri $url `
        -ContentType "application/xml" `
        -Method POST `
        -Body $fileContent `
        -UserAgent "OCS_POWERSHELL_INJECTOR_V2.8" `
        -Headers $headers
    }


    if($webRequestStatus.StatusCode -eq 200){
        Write-InfoLog("Injecting file $fileName => OK")
    }else{
        Write-InfoLog("Injecting file $fileName => ERROR " + $webRequestStatus.StatusCode)
    }
}

Write-InfoLog("Starting injector")

#### Help trigger ####
if($help.IsPresent){
    Get-InjectorHelper
    Exit
}

#### Test for basic parameters ####
if($url.isPresent -eq $false){
    Write-InfoLog("URL Missing, please provide one !")
    Exit
}

# Manage Auth
if(-not ([string]::IsNullOrEmpty($user)) -And -not ([string]::IsNullOrEmpty($pass))){
    Write-InfoLog("Auth required, setting headers...")

    $pair = "$($user):$($pass)"
    $encodedCreds = [System.Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
    $basicAuthValue = "Basic $encodedCreds"

    Write-InfoLog("Auth basic : $encodedCreds")

    $headers = @{
        "Authorization" = $basicAuthValue; "Cache-Control" = "no-cache"
    }
}else{
    $headers = @{
        "Cache-Control" = "no-cache"
    }
}

$pathOk = $false

# File inject management
if($file -And (Test-Path -Path $file) -eq $true){
    $pathOk = $true
    Send-File $file $headers
    Exit
}

# Directory inject management
if ($directory -And (Test-Path -Path $directory) -eq $true) {
    Write-InfoLog("Injecting files present in the directory=> $directory")
    Get-ChildItem $directory -Filter *.ocs |
    Foreach-Object {
        Send-File $_.FullName $headers
    }
    $pathOk = $true
    Exit
}

# Path not working
if($pathOk -eq $false){
    Write-InfoLog("File or Directory provided is wrong or not accessible, please check your arguments")
    Exit
}
