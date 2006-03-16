###############################################################################
## OCSINVENTORY-NG
## Copyleft Pascal DANEK 2005
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory;

use strict;

BEGIN{
	# Initialize option
	push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
		'HANDLER_PROLOG_READ' => undef,
		'HANDLER_PROLOG_RESP' => \&_registry_prolog_resp,
		'HANDLER_INVENTORY' => \&_registry_main,
		'REQUEST_NAME' => undef,
		'HANDLER_REQUEST' => undef,
		'HANDLER_DUPLICATE' => \&_registry_duplicate,
		'TYPE' => OPTION_TYPE_SYNC
	};
}

# Default
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_REGISTRY'} = 1;

sub _registry_main{

	return unless $ENV{'OCS_OPT_REGISTRY'};
	
	my $current_context = shift;
	
	my $dbh = $current_context->{'DBI_HANDLE'};
	my $DeviceID = $current_context->{'DEVICEID'};
	my $update = $current_context->{'EXIST_FL'};
	my $data = $current_context->{'DATA'};

	my $result;
	unless($result = XML::Simple::XMLin( $$data, SuppressEmpty => 1, ForceArray => ['REGISTRY'] )){
		return(1);
	}

	my $sth = $dbh->prepare('INSERT INTO registry(DEVICEID, NAME, REGVALUE) VALUES(?, ?, ?)');

	if($update){
		if(!$dbh->do('DELETE FROM registry WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}

	my $array = $result->{CONTENT}->{REGISTRY};

	for(@$array){
		if(!$sth->execute($DeviceID, $_->{NAME}, $_->{REGVALUE})){
			return(1);
		}
	}
	return(0);
}


sub _registry_prolog_resp{

	return unless $ENV{'OCS_OPT_REGISTRY'};
	
	my $current_context = shift;
	my $resp = shift;
	
	my $dbh = $current_context->{'DBI_HANDLE'};

	# Sync option
	return if $resp->{'RESPONSE'} eq 'STOP';

	my $request;
	my $row;
	#################################
	#REGISTRY
	#########
	# Ask computer to retrieve the requested registry keys
	my @registry;
	$request=$dbh->prepare("SELECT * FROM regconfig");
	$request->execute;
	while($row = $request->fetchrow_hashref){
		push @registry,
			{
				'REGTREE' =>  $row->{'REGTREE'} ,
				'REGKEY'  =>  $row->{'REGKEY'} ,
				'NAME'    =>  $row->{'NAME'} ,
				'content' =>  $row->{'REGVALUE'}
			};
	}

	if(@registry){
		push @{$$resp{'OPTION'}}, {
					'NAME'  => [ 'REGISTRY' ],
					'PARAM'	=> \@registry
				};
		return(1);
	}else{
		return(0);
	}
}

sub _registry_duplicate{	
	my $current_context = shift;
	my $device = shift;
	
	my $dbh = $current_context->{'DBI_HANDLE'};
	my $DeviceID = $current_context->{'DATABASE_ID'};

	# If we encounter problems, it aborts whole replacement
	return $dbh->do('UPDATE registry SET HARDWARE_ID=? WHERE HARDWARE_ID=?',{}, $DeviceID, $device);
}
1;
