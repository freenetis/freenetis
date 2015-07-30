#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Std;
use Data::Dumper;
use warnings;

if ($#ARGV != 1) {
  print "Wrong number of parameters", "\n";
  exit 0;
}

use SOAP::Lite +autodispatch =>
   uri => "http://sip.nfx.czf/lBilling",
   proxy => 'https://'.$ARGV[0].':'.$ARGV[1].'@sip.nfx.czf/cgi-bin/admin/lbilling/soap.pl';

my $lbilling = lBilling->new();

unless ( $lbilling ) {
  print "Could not create SOAP instance", "\n";
  exit 0;
}
exit 1;
