Aeronautics\Mustang
===================

The Insanely Modular CSS Framework, which is in **heavy development**, therefore **highly unstable**. It's small (260 lines) and have few dependencies (Sabberworm\PHP-CSS-PARSER).

Preview
-------

Mustang works mainly by extending CSS rules.

    ul li {
        mustang-really-allows-you-to-create-whatever-rules-you: want;
    }

Libraries also reuse selectors using the `&` wildcard and apply bulk selectors using the `**` wildcard. Libraries
also can accept parameters.

These rules and selectors are then processed against a library code, which handles what the custom property does.

Framework code is organized then by properties:

    style/            <-- Folder with all CSS stuff
       \- library/    <-- Libraries
        \- typography
         \- ubuntu.cssl
         |- ubuntu-web.cssl
         |- plain.cssl
         |- markdown.cssl
        |- fix         
         \- compat.cssl
         |- clearfix.cssl
       |- style.cssl  <-- Your file

Look for the `test/data` and `style` folders for samples. There are some libraries already
in progress!

