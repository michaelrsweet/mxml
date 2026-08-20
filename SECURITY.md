Security Policy
===============

This file describes how security issues are reported and handled, and what the
expectations are for security issues reported to this project.


Reporting a Security Bug
------------------------

For the purposes of this project, a security bug is a software defect that
allows a *local or remote user* to gain unauthorized access or privileges on the
host computer with a production release of the software.  Such defects should be
reported to the project security advisory page at
<https://github.com/michaelrsweet/mxml/security/advisories>.

> *Note:* If you've found a software defect that allows a *program* to gain
> unauthorized access or privileges on the host computer or causes the program
> to crash, or the problem exists in unreleased code, that defect should be
> reported as an ordinary project issue at
> <https://github.com/michaelrsweet/mxml/issues>.


Supported Versions
------------------

All production releases of this software are subject to this security policy.  A
production release is tagged and given a semantic version number of the form:

    MAJOR.MINOR.PATCH

where "MAJOR" is an integer starting at 1 and "MINOR" and "PATCH" are integers
starting at 0.  A feature release has a "PATCH" value of 0, for example:

    1.0.0
    1.1.0
    2.0.0

Beta releases and release candidates are *not* production releases and use
semantic version numbers of the form:

    MAJOR.MINORbNUMBER
    MAJOR.MINORrcNUMBER

where "MAJOR" and "MINOR" identify the new feature release version number and
"NUMBER" identifies a beta or release candidate number starting at 1, for
example:

    1.0b1
    1.0b2
    1.0rc1

Similarly, untagged code in the Git repository is *not* a production release.
