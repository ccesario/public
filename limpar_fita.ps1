Add-PSSnapin VeeamPSSnapin
$JobName = (Get-VBRJob | where {$_.isRunning -eq $True}).Name
$query = Get-VBRTapeJob | Select-Object Id,Name, Object, Target
$count = $query | Measure-Object
$count = $count.count
foreach ($object in $query) {
	$Id = [string]$object.Id
	$Name = [string]$object.Name
	$Jobs = [string]$object.Object.Name
	if ($Jobs -match [string]$JobName[0]) {
		$MediaPool = [string]$object.Target
		$free = Get-VBRTapeMediaPool -Name "Free"
		$pool = Get-VBRTapeMediaPool | where {$_.Name -eq $MediaPool}
		$tape = Get-VBRTapeMedium -MediaPool $pool | where {$_.isExpired -eq $True}
		if ([string]::IsNullOrEmpty($tape)) { }
		else {
			Move-VBRTapeMedium -Medium $tape -MediaPool $free
		}
	}
}
