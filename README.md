IfAuthEx Plugin for DokuWiki
===
**Toggle DokuWiki page content based on users and groups with arbitrary boolean expressions.**  

**Latest release: v0.0, [download here](https://git.mittelab.org/proj/ifauthex-dokuwiki-plugin/-/jobs/artifacts/v0.0/raw/ifauthex.zip?job=package).**  
You can use the download link URL to install it on DokuWiki directly.

**GitHub mirror (issues and PR): [https://github.com/mittelab/ifauthex-dokuwiki-plugin](https://github.com/mittelab/ifauthex-dokuwiki-plugin)**  
Feel free to [open an issue](https://github.com/mittelab/ifauthex-dokuwiki-plugin/issues) or [make a pull request](https://github.com/mittelab/ifauthex-dokuwiki-plugin/pulls) here.

**Documentation: [https://www.dokuwiki.org/plugin:ifauthex](https://www.dokuwiki.org/plugin:ifauthex)**

**Main repository: [https://git.mittelab.org/proj/ifauthex-dokuwiki-plugin](https://git.mittelab.org/proj/ifauthex-dokuwiki-plugin)**  
Development, testing and packaging happens on the main repo.

**Last commit:** [![pipeline status](https://git.mittelab.org/proj/ifauthex-dokuwiki-plugin/badges/master/pipeline.svg)](https://git.mittelab.org/proj/ifauthex-dokuwiki-plugin/commits/master)

Rationale
---
This plugin intends to replace the [IfAuth plugin](https://www.dokuwiki.org/plugin:ifauth), but
it's an independent reboot. IfAuth can only "or" different expressions, therefore it's not possible
to target expressions like `@user && !@admin`. IfAuthEx fixes these limtations extending the syntax
to arbitrary boolean expression (in for a penny...), that uses PHP standard logical operators `||`,
`&&`, `!`, as well as parentheses.

Plugin notes
---

All documentation for this plugin can be found at
[https://www.dokuwiki.org/plugin:ifauthex](https://www.dokuwiki.org/plugin:ifauthex)

If you install this plugin manually, make sure it is installed in
`lib/plugins/ifauthex/` - if the folder is called different it
will not work!

Please refer to [http://www.dokuwiki.org/plugins](http://www.dokuwiki.org/plugins) for additional info
on how to install plugins in DokuWiki.

---

Copyright (C) Pietro Saccardi <lizardm4@gmail.com>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See the LICENSING file for details
