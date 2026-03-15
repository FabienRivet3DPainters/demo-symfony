$envFile = Join-Path $PSScriptRoot "../.env.local"
Get-Content $envFile | ForEach-Object {
    if ($_ -match '^([^#][^=]*)=(.*)$') {
        [System.Environment]::SetEnvironmentVariable($matches[1].Trim(), $matches[2].Trim())
    }
}

k6 cloud run `
  --env BASE_URL="$env:BASE_URL" `
  --env K6_USERNAME="$env:K6_USERNAME" `
  --env K6_PASSWORD="$env:K6_PASSWORD" `
  k6/load-test.js$envFile = Join-Path $PSScriptRoot "../.env.local"
Get-Content $envFile | ForEach-Object {
    if ($_ -match '^([^#][^=]*)=(.*)$') {
        [System.Environment]::SetEnvironmentVariable($matches[1].Trim(), $matches[2].Trim())
    }
}

k6 cloud run `
  --env BASE_URL="$env:BASE_URL" `
  --env K6_USERNAME="$env:K6_USERNAME" `
  --env K6_PASSWORD="$env:K6_PASSWORD" `
  k6/load-test.js