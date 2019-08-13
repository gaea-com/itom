#!/usr/bin/perl
BEGIN {
    $ENV{'PATH'} = "/usr/local/bin:$ENV{'PATH'}"
};

die "Usage: perl $0 <branch>" if @ARGV < 1;

my $branch = $ARGV[0];
my $version = $ARGV[1];

my $root_path = '/data/itom-web/';
my $git_url = 'https://gitlab.gaeamobile-inc.net/kaituo.yang/itom.git';
my $stage_path = '/data/itom-web/stage';
my $dist_path = '/data/itom-web/itom/web/itom/dist/itom';
my $git_path = '/data/itom-web/itom/';

print "build using branch: $branch .. \n";
unless(-e $git_path){
	if(system("cd $root_path && git clone $git_url") != 0){
		die "git clone fail $@\n";
	}
}

if (system("cd $git_path && git clean -f && git pull && git checkout $branch") != 0) {
    die "git update fail $@\n";
}

print "copying...\n";
`rm -rf $stage_path/itom`;
`cp -R $dist_path $stage_path`;

print "make docker image \n";
`docker build -t itom-web:$version .`;

print "done!\n";
