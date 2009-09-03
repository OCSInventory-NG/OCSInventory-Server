#!/usr/bin/perl -s

$f = $f || '/var/log/ocsinventory-server/activity.log';

unless($a){
	$a = 0;
	@lines = `cat $f`;
}
else{
	@lines = `$f.$a.gz`;
}

push @heures, sprintf("%02i",$_) for(0..23);
push @minutes, sprintf("%02i",$_) for(0..59);


@lines = grep { /^[^;]+;[^;]+;[^;]+;[^;]+;[^;]+$v;[^;]+;[^;]+$/i } @lines if defined($v); 

@prologs = grep { /;(?:102|100);/i } @lines; 
@accepts = grep { /;100;/i } @lines;
@incomings = grep { /;104;/i } @lines;
@arrived = grep { /;101;/i } @lines;
@errors = grep { /;515;/i } @lines;
@agents = grep { /deploy.+transmitted/i } @lines;
@new = grep { /;103;/i } @lines;
@dup = grep { /;300;/i } @lines;
@gr_nv = grep { /;306;/i } @lines;
@gr_rv = grep { /;307;/i } @lines;



print 	"\n\nSynthese - ",scalar(localtime())," - $a jours\n\n",
	"Prologs: ", scalar(@prologs),"\n",
	"Accepted: ", scalar(@accepts),"\n",
	"New: ",  scalar(@new),"\n",
	"Incomings: ", scalar(@incomings),"\n",
	"Auto duplicates: ", scalar(@dup),"\n",
	"Transmitted: ", scalar(@arrived),"\n",
	"Errors: ", scalar(@errors),"\n",
	"Groups out-of-date: ",scalar(@gr_nv),"\n",
	"Groups revalidated: ",scalar(@gr_nv),"\n",
	"Déploiement: ", scalar(@agents),"\n\n" if $s;

per_hour() if $r;
if(defined($h)){
	per_deca("$h") if $d;
	per_minutes("$h") if $m;
}

sub per_hour 
{ 
	print "\n\nRecapitulatif par heure - ",scalar(localtime())," - $a jours\n\n";
	print "\t\t\tPrologs\t\tAccepted\tIncomings\tNew\t\tDuplicates\tTransmitted\tErrors\tgr:ood\t:gr:reval\t\tDeployed\n";
	for $heure (@heures){
		print "$heure heures :\t";
		print "\t",scalar(grep { /$heure(?::\d\d){2}/ } @prologs),"\t"; 
		print "\t",scalar(grep { /$heure(?::\d\d){2}/ } @accepts),"\t";
		print "\t",scalar(grep { /$heure(?::\d\d){2}/ } @incomings),"\t";
		print "\t",scalar(grep { /$heure(?::\d\d){2}/ } @new),"\t";
		print "\t",scalar(grep { /$heure(?::\d\d){2}/ } @dup),"\t";
		print "\t",scalar(grep { /$heure(?::\d\d){2}/ } @arrived),"\t";
		print "\t",scalar(grep { /$heure(?::\d\d){2}/ } @errors),"\t";
		print "\t",scalar(grep { /$heure(?::\d\d){2}/ } @gr_nv),"\t";
		print "\t",scalar(grep { /$heure(?::\d\d){2}/ } @gr_rv),"\t";
		print "\t",scalar(grep { /$heure(?::\d\d){2}/ } @agents),"\n";
		
	}
}

sub per_minutes
{
	$heure = shift;
	$heure = sprintf("%02i",$heure);
	
	print scalar(localtime()),"\n\n";
	print "\n\nRecapitulatif minute par minute pour $heure heures - ",scalar(localtime())," - $ai jours\n\n";
	print "\t\t\t\tPrologs\t\tAccepted\tIncomings\tNew\t\tDuplicates\tTransmitted\tErrors\tgr:ood\t:gr:reval\t\tDeployed\n";
	for $minute (@minutes){
		print "$heure heure $minute minutes :\t";
		print "\t",scalar(grep { /$heure:$minute:\d\d/ } @prologs),"\t";
		print "\t",scalar(grep { /$heure:$minute:\d\d/ } @accepts),"\t";
		print "\t",scalar(grep { /$heure:$minute:\d\d/ } @incomings),"\t";
		print "\t",scalar(grep { /$heure:$minute:\d\d/ } @new),"\t";
		print "\t",scalar(grep { /$heure:$minute:\d\d/ } @dup),"\t";
		print "\t",scalar(grep { /$heure:$minute:\d\d/ } @arrived),"\t";
		print "\t",scalar(grep { /$heure:$minute:\d\d/ } @errors),"\t";
		print "\t",scalar(grep { /$heure:$minute:\d\d/ } @gr_nv),"\t";
		print "\t",scalar(grep { /$heure:$minute:\d\d/ } @gr_rv),"\t";
		print "\t",scalar(grep { /$heure:$minute:\d\d/ } @agents),"\n";
	}
}

sub per_deca
{
	$heure = shift;
	@deca = (0..5);
	
	print scalar(localtime()),"\n\n";
	print "\n\nRecapitulatif par dix minutes pour $heure heures - ",scalar(localtime())," - $a jours\n\n";
	print "\t\t\t\tPrologs\t\tAccepted\tIncomings\tNew\t\tDuplicates\tTransmitted\tErrors\tgr:ood\t:gr:reval\t\tDeployed\n";
	for $minute (@deca){
		print "$heure heure ($minute*10) :\t";
		print "\t",scalar(grep { /$heure:$minute\d:\d\d/ } @prologs),"\t";
		print "\t",scalar(grep { /$heure:$minute\d:\d\d/ } @accepts),"\t";
		print "\t",scalar(grep { /$heure:$minute\d:\d\d/ } @incomings),"\t";
		print "\t",scalar(grep { /$heure:$minute\d:\d\d/ } @new),"\t";
		print "\t",scalar(grep { /$heure:$minute\d:\d\d/ } @dup),"\t";
		print "\t",scalar(grep { /$heure:$minute\d:\d\d/ } @arrived),"\t";
		print "\t",scalar(grep { /$heure:$minute\d:\d\d/ } @errors),"\t";
		print "\t",scalar(grep { /$heure:$minute\d:\d\d/ } @gr_nv),"\t";
		print "\t",scalar(grep { /$heure:$minute\d:\d\d/ } @gr_rv),"\t";
		print "\t",scalar(grep { /$heure:$minute\d:\d\d/ } @agents),"\n";
	}
														 
}

