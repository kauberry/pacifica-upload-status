Name:		pacifica-upload-status
Epoch:		1
Version:	0.99.8
Release:	1%{?dist}
Summary:	The pacifica upload status web page
Group:		System Environment/Libraries
License:	GPLv2
URL:		http://www.example.com/
Source0:	%{name}-%{version}.tar.gz
BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildArch:      noarch

BuildRequires:	rsync

%description
This is a short description

%prep
%setup -q

%build
rm -f system index.php
mv websystem/system system
mv websystem/index.php index.php
rm -rf websystem resources

%install
mkdir -p %{buildroot}/var/www/myemsl/status
mkdir -p %{buildroot}/etc/php.d
rsync -r application index.php system %{buildroot}/var/www/myemsl/status/
mkdir -p %{buildroot}/var/www/myemsl/status/application/logs
cp a*.png %{buildroot}/var/www/myemsl/status/
cp favicon*.* %{buildroot}/var/www/myemsl/status/
cp ms*.png %{buildroot}/var/www/myemsl/status/
cp manifest.json %{buildroot}/var/www/myemsl/status/
cp browserconfig.xml %{buildroot}/var/www/myemsl/status/
cp safari*.svg %{buildroot}/var/www/myemsl/status/
cp php_myemsl.ini %{buildroot}/etc/php.d/myemsl.ini

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
/var/www/myemsl/status
/etc/php.d/myemsl.ini
%defattr(-,apache,apache,-)
/var/www/myemsl/status/application/logs

%changelog
* Mon Mar 21 2016 David Brown <david.brown@pnnl.gov> 0.99.0-1
- Initial RHEL release.
