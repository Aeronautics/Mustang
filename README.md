Aeronautics\Mustang
===================

The Insanely Modular CSS Framework, which is in **heavy development**, therefore **highly unstable**.

Preview
-------

Mustang works mainly by extending CSS rules.

    ul li {
        mustang-really-allows-you-to-create-whatever-rules-you: want;
    }

These rules are then processed against a library code, which handles what the custom property does.

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

