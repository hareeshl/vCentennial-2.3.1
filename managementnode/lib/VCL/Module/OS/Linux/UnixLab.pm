#!/usr/bin/perl -w
###############################################################################
# $Id: Linux.pm 795834 2009-07-20 13:37:52Z arkurth $
###############################################################################
# Licensed to the Apache Software Foundation (ASF) under one or more
# contributor license agreements.  See the NOTICE file distributed with
# this work for additional information regarding copyright ownership.
# The ASF licenses this file to You under the Apache License, Version 2.0
# (the "License"); you may not use this file except in compliance with
# the License.  You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
###############################################################################

=head1 NAME

VCL::Module::OS::Linux.pm - VCL module to support Linux operating systems

=head1 SYNOPSIS

 Needs to be written

=head1 DESCRIPTION

 This module provides VCL support for Linux operating systems.

=cut

##############################################################################
package VCL::Module::OS::Linux::UnixLab;

# Specify the lib path using FindBin
use FindBin;
use lib "$FindBin::Bin/../../../..";

# Configure inheritance
use base qw(VCL::Module::OS::Linux);

# Specify the version of this module
our $VERSION = '2.3.1';

# Specify the version of Perl to use
use 5.008000;

use strict;
use warnings;
use diagnostics;

use VCL::utils;

##############################################################################

=head1 OBJECT METHODS

=cut

#/////////////////////////////////////////////////////////////////////////////

=head2 revoke_access

 Parameters  :
 Returns     :
 Description :

=cut

sub revoke_access {
	my $self = shift;
	if (ref($self) !~ /unixlab/i) {
		notify($ERRORS{'CRITICAL'}, 0, "subroutine was called as a function, it must be called as a class method");
		return 0;
	}

	my $computer_ip_address = $self->data->get_computer_ip_address;
	my $computer_node_name  = $self->data->get_computer_node_name();
	my $user_login_id       = $self->data->get_user_login_id();
	my $identity            = $self->data->get_image_identity();

	if (!$user_login_id) {
		notify($ERRORS{'WARNING'}, 0, "user could not be determined");
		return 0;
	}

	if (!$computer_node_name) {
		notify($ERRORS{'WARNING'}, 0, "computer node name could not be determined");
		return 0;
	}

	if (!$identity) {
		notify($ERRORS{'WARNING'}, 0, "image identity keys could not be determined");
		return 0;
	}

	# Filler for clientdata file
	my $remoteIP = "127.0.0.1";
	my $state    = "timeout";

	my @lines;
	my $l;
	# create clientdata file
	my $clientdata = "/tmp/clientdata.$computer_ip_address";
	if (open(CLIENTDATA, ">$clientdata")) {
		print CLIENTDATA "$state\n";
		print CLIENTDATA "$user_login_id\n";
		print CLIENTDATA "$remoteIP\n";
		close CLIENTDATA;

		# scp to hostname
		my $target = "vclstaff\@$computer_ip_address:/home/vclstaff/clientdata";
		if (run_scp_command($clientdata, $target, $identity, "24")) {
			notify($ERRORS{'OK'}, 0, "Success copied $clientdata to $target");
			unlink($clientdata);

			# send flag to activate changes
			my @sshcmd = run_ssh_command($computer_ip_address, $identity, "echo 1 > /home/vclstaff/flag", "vclstaff", "24");
			notify($ERRORS{'OK'}, 0, "setting flag to 1 on $computer_ip_address");

			my $nmapchecks = 0;
			# return nmap check

			NMAPPORT:
			if (!(nmap_port($computer_ip_address, 22))) {
				return 1;
			}
			else {
				if ($nmapchecks < 5) {
					$nmapchecks++;
					sleep 1;
					notify($ERRORS{'OK'}, 0, "port 22 not closed yet calling NMAPPORT code block");
					goto NMAPPORT;
				}
				else {
					notify($ERRORS{'WARNING'}, 0, "port 22 never closed on client $computer_ip_address");
					return 0;
				}
			} ## end else [ if (!(nmap_port($computer_ip_address, 22)))
		} ## end if (run_scp_command($clientdata, $target, ...
		else {
			notify($ERRORS{'OK'}, 0, "could not copy src=$clientdata to target=$target");
			return 0;
		}
	} ## end if (open(CLIENTDATA, ">$clientdata"))
	else {
		notify($ERRORS{'WARNING'}, 0, "could not open /tmp/clientdata.$computer_ip_address $! ");
		return 0;
	}

	return 1;

} ## end sub revoke_access

#/////////////////////////////////////////////////////////////////////////////

=head2 reserve

 Parameters  : called as an object
 Returns     : 1 - success , 0 - failure
 Description : adds user 

=cut

sub reserve {
	my $self = shift;
	if (ref($self) !~ /unixlab/i) {
		notify($ERRORS{'CRITICAL'}, 0, "subroutine was called as a function, it must be called as a class method");
		return 0;
	}

	return 1;
}

#/////////////////////////////////////////////////////////////////////////////

=head2 grant_access

 Parameters  : called as an object
 Returns     : 1 - success , 0 - failure
 Description :  adds username to external_sshd_config and and starts sshd with custom config

=cut

sub grant_access {
	my $self = shift;
	if (ref($self) !~ /unixlab/i) {
		notify($ERRORS{'CRITICAL'}, 0, "subroutine was called as a function, it must be called as a class method");
		return 0;
	}

	my $user                = $self->data->get_user_login_id();
	my $computer_node_name  = $self->data->get_computer_node_name();
	my $computer_ip_address = $self->data->get_computer_ip_address;
	my $identity            = $self->data->get_image_identity;
	my $remoteIP            = $self->data->get_reservation_remote_ip();
	my $state               = "new";


	notify($ERRORS{'OK'}, 0, "In grant_access routine $user,$computer_node_name");

	my ($package, $filename, $line, $sub) = caller(0);

	# create clientdata file
	my $clientdata = "/tmp/clientdata.$computer_ip_address";
	if (open(CLIENTDATA, ">$clientdata")) {
		print CLIENTDATA "$state\n";
		print CLIENTDATA "$user\n";
		print CLIENTDATA "$remoteIP\n";
		close CLIENTDATA;

		# scp to hostname
		my $target = "vclstaff\@$computer_ip_address:/home/vclstaff/clientdata";
		if (run_scp_command($clientdata, $target, $identity, "24")) {
			notify($ERRORS{'OK'}, 0, "Success copied $clientdata to $target");
			unlink($clientdata);

			# send flag to activate changes
			my @sshcmd = run_ssh_command($computer_ip_address, $identity, "echo 1 > /home/vclstaff/flag", "vclstaff", "24");
			notify($ERRORS{'OK'}, 0, "setting flag to 1 on $computer_ip_address");

			my $nmapchecks = 0;

			NMAPPORT:
			if (nmap_port($computer_ip_address, 22)) {
				notify($ERRORS{'OK'}, 0, "sshd opened");
				return 1;
			}
			else {
				if ($nmapchecks < 6) {
					$nmapchecks++;
					sleep 1;
					#notify($ERRORS{'OK'},0,"calling NMAPPORT code block");
					goto NMAPPORT;
				}
				else {
					notify($ERRORS{'WARNING'}, 0, "port 22 never opened on client $computer_ip_address");
					return 0;
				}
			} ## end else [ if (nmap_port($computer_ip_address, 22))
		} ## end if (run_scp_command($clientdata, $target, ...
		else {
			notify($ERRORS{'WARNING'}, 0, "could not copy src=$clientdata to target= $target");
			return 0;
		}
	} ## end if (open(CLIENTDATA, ">$clientdata"))
	else {
		notify($ERRORS{'WARNING'}, 0, "could not open /tmp/clientdata.$computer_ip_address $! ");
		return 0;
	}

	return 1;

} ## end sub grant_access


#/////////////////////////////////////////////////////////////////////////////

=head2 post_reserve

 Parameters  : 
 Returns     : 0,1
 Description : currently empty to prevent Linux.pm form trying to login 
		different user

=cut
sub post_reserve {
	return 1;
}

#/////////////////////////////////////////////////////////////////////////////

=head2 sanitize

 Parameters  :
 Returns     :
 Description :

=cut

sub sanitize {
	my $self = shift;
	if (ref($self) !~ /unixlab/i) {
		notify($ERRORS{'CRITICAL'}, 0, "subroutine was called as a function, it must be called as a class method");
		return;
	}

	my $computer_node_name = $self->data->get_computer_node_name();

	# Delete all user associated with the reservation
	if ($self->revoke_access()) {
		notify($ERRORS{'OK'}, 0, "access has been disabled for $computer_node_name");
	}
	else {
		notify($ERRORS{'WARNING'}, 0, "failed to delete users from $computer_node_name");
		return 0;
	}

	notify($ERRORS{'OK'}, 0, "$computer_node_name has been sanitized");
	return 1;
} ## end sub sanitize

#/////////////////////////////////////////////////////////////////////////////

=head2 get_current_image_name

 Parameters  : None
 Returns     : If successful: string
               If failed: 0
 Description : Returns the name of the reservation image. This is used in
               reclaim.pm to determine if a computer needs to be reloaded or
					sanitized. Lab machines should always be sanitized.

=cut

sub get_current_image_name {
	my $self = shift;
	if (ref($self) !~ /VCL::Module/i) {
		notify($ERRORS{'CRITICAL'}, 0, "subroutine was called as a function, it must be called as a class method");
		return;
	}

	my $image_name = $self->data->get_image_name();
	if ($image_name) {
		notify($ERRORS{'DEBUG'}, 0, "returning reservation image name: $image_name");
		return $image_name;
	}
	else {
		notify($ERRORS{'WARNING'}, 0, "failed to retrieve reservation image name");
		return 0;
	}
}

#/////////////////////////////////////////////////////////////////////////////

=head2 check_connection_on_port

 Parameters  : $port
 Returns     : (connected|conn_wrong_ip|timeout|failed)
 Description : uses netstat to see if any thing is connected to the provided port
 
=cut

sub check_connection_on_port {
        my $self = shift;
        if (ref($self) !~ /VCL::Module/i) {
                notify($ERRORS{'CRITICAL'}, 0, "subroutine was called as a function, it must be called as a class method");
                return;
        }

        my $management_node_keys        = $self->data->get_management_node_keys();
        my $computer_node_name          = $self->data->get_computer_node_name();
        my $remote_ip                   = $self->data->get_reservation_remote_ip();
        my $computer_ip_address         = $self->data->get_computer_ip_address();
        my $request_state_name          = $self->data->get_request_state_name();

        my $port = shift;
        if (!$port) {
                notify($ERRORS{'WARNING'}, 0, "port variable was not passed as an argument");
                return "failed";
        }

        my $ret_val = "no";
        my $command = "netstat -an";
        my ($status, $output) = run_ssh_command($computer_node_name, $management_node_keys, $command, 'vclstaff', 24, 1);
        notify($ERRORS{'DEBUG'}, 0, "checking connections on node $computer_node_name on port $port");
        foreach my $line (@{$output}) {
                if ($line =~ /Connection refused|Permission denied/) {
                    chomp($line);
                    notify($ERRORS{'WARNING'}, 0, "$line");
                    if ($request_state_name =~ /reserved/) {
                        $ret_val = "failed";
                    }
                    else {
                         $ret_val = "timeout";
                    }
                    return $ret_val;
                 } ## end if ($line =~ /Connection refused|Permission denied/)
                 if ($line =~ /tcp\s+([0-9]*)\s+([0-9]*)\s($computer_ip_address:$port)\s+([.0-9]*):([0-9]*)(.*)(ESTABLISHED)/) {
                     if ($4 eq $remote_ip) {
                         $ret_val = "connected";
                         return $ret_val;
                     }
                     else {
                          #this isn't the remoteIP
                          $ret_val = "conn_wrong_ip";
                          return $ret_val;
                     }
                 }    # Linux
		 if ($line =~ /tcp\s+([0-9]*)\s+([0-9]*)\s::ffff:($computer_ip_address:$port)\s+::ffff:([.0-9]*):([0-9]*)(.*)(ESTABLISHED) /) {
                     if ($4 eq $remote_ip) {
                         $ret_val = "connected";
                         return $ret_val;
                     }
                     else {
                       #this isn't the remoteIP
                          $ret_val = "conn_wrong_ip";
                          return $ret_val;
                     }
                } ##
		if ($line =~ /\s*($computer_ip_address\.$port)\s+([.0-9]*)\.([0-9]*)(.*)(ESTABLISHED)/) {
                     if ($4 eq $remote_ip) {
                         $ret_val = "connected";
                         return $ret_val;                       
                     }
                     else {
                       #this isn't the remoteIP
                          $ret_val = "conn_wrong_ip";
                          return $ret_val;
                     }
                } ##	
		
		
        }
        return $ret_val;
}

#/////////////////////////////////////////////////////////////////////////////

1;
__END__

=head1 SEE ALSO

L<http://cwiki.apache.org/VCL/>

=cut
