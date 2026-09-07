Security Policy
===============

This file describes how security issues are reported and handled, and what the
expectations are for security issues reported to this project.


Supported Versions
------------------

This security policy only applies to production releases of this software.  A
production release is tagged and given a semantic version number of the form
"MAJOR.MINOR.PATCH" where "MAJOR" is an integer starting at 1 and "MINOR" and
"PATCH" are integers starting at 0.

> *Note:* Please report security vulnerabilities that only affect unreleased
> code as regular GitHub issues to
> <https://github.com/michaelrsweet/mxml/issues>.


What is a Security Bug?
-----------------------

Not every bug is a security bug.

Certain bugs that might be considered security bugs in a program, such as bugs
that lead to a Denial of Service, are *not* considered security bugs simply
because this project *does not provide a service*.  Some might argue that, "my
server uses this library and the bug in this library causes a denial of
service for my server", however it is the responsibility of the *server* to
protect against DoS attacks, not a subordinate library, because only the
server knows what is an appropriate use of memory, CPU, time, and other
resources.

Similarly, bugs caused by incorrect API usage such as passing `NULL` pointers
where such pointers are not allowed, passing the wrong kinds of pointers or
objects to an API, or using a private API are not security bugs because they
are not caused by an attacker but by the developer.

Finally, bugs that only exist in unreleased (non-production) or inactive code
are not security bugs because they do not affect ordinary users.

If the bug you've found falls into one of these three categories, please report
the bug as an the ordinary GitHub issue at
<https://github.com/michaelrsweet/mxml/issues>.


Reporting a Security Bug
------------------------

Vulnerabilities should be reported to the project security advisory page at
<https://github.com/michaelrsweet/mxml/security/advisories>.

Provide details, impact, reproducer, affected versions, workarounds, and a
patch for the vulnerability, if applicable.

You can expect a response within 5 business days.


Security Bug Processing
-----------------------

Security bugs are processed privately until disclosed.

A Common Vulnerability Scoring System (CVSS) score is calculated to determine
the severity of the issue.  Issues with a CVSS score of 7 or more use
*responsible disclosure* where the security issue (and its fix) is disclosed
only after a mutually-agreed period of time (the "embargo date").  The issue
and fix are shared amongst and reviewed by the key stakeholders and the
CERT/CC, and a CVE is requested from the GitHub CNA.  Fixes are released to the
public on the agreed-upon date.

Fixes for less serious issues are pushed to the public repository and published
as soon as they are ready using the GitHub Security Advisory (GHSA) identifier.

Security bug changes are listed in the `CHANGES.md` file with a SECURITY prefix
and the CVSS score, for example:

- SECURITY-7.8: Description of serious issue (CVE-YYYY-NNNNN)
- SECURITY-2.2: Description of less serious issue (GHSA-xxxx-xxxx-xxx)
