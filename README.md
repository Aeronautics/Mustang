Aeronautics\Mustang
===================

The Insanely Modular CSS Ecosystem. **Which is largely unfinished!**

  * We keep the **aero.as/style** service in the cloud. (not really yet)
  * The **Library** is a set of reusable CSS code. (well, some of it)
  * Our **Processor** is the core engine of CSS reuse. (somehow slow yet)
    
CSS means _Cascading Style Sheets_, and it's a language to style web pages.

The Processor
-------------

Mustang libraries are written in the same _valid_ syntax as CSS. They extend CSS 
using it's core concepts: cascading, selectors and properties. Here is a sample 
CSSL that reuses the CSS **cascade**:

    a:focus {
        outline: thin dotted;
    }

    a:active,
    a:hover {
        outline: 0;
    }

    abbr[title] {
        border-bottom: 1px dotted;
    }

    img {
        border: 0;
    }

This is the `normalize/borders.cssl` library that normalizes borders on several
elements. To use this library, you may choose the selectors you want to import:

    @import 'normalize/borders.cssl';

    img, abbr {
        normalize: borders;
    }

This would generate the output ommiting the `a` rules:

    abbr[title] {
        border-bottom: 1px dotted;
    }

    img {
        border: 0;
    }

You may also import all selectors using the `-all` placeholder:

    @import 'normalize/borders.cssl';

    -all {
        normalize: borders;
    }
    
Libraries can often reuse **selectors** as well. Take a look at this sample:

    -selector:after {
	    content: ".";
	    display: block;
	    clear: both;
	    visibility: hidden;
	    line-height: 0;
	    height: 0;
    }
     
    -selector {
	    display: inline-block;
    }
     
    html[xmlns] -selector {
	    display: block;
    }
     
    * html -selector {
	    height: 1%;
    }
    
If you're familiar with CSS, you may have recognized this as a clearfix snippet
applied to `-selector`. This is the `fix/compat.cssl` library that can be used
like this:

    @import 'fix/compat.cssl';
    
    #foo, #bar {
        -fix: compat;
    }
    
The `-selector` token is a placeholder for whatever the selectors you apply to
this libraries. Most libraries work this way. Here is the output:

    #foo:after, #bar:after {
	    content: ".";
	    display: block;
	    clear: both;
	    visibility: hidden;
	    line-height: 0;
	    height: 0;
    }
     
    #foo, #bar {
	    display: inline-block;
    }
     
    html[xmlns] #foo, html[xmlns] #bar {
	    display: block;
    }
     
    * html #foo, * html #bar {
	    height: 1%;
    }

You can now reuse the cascade to import selectors and reuse these selectors as
well, but there is a final piece of the Processor. Let's see the **property**
reuse:

    body {
        color: -param-0;
        background: -param-1;
    }

This is the `ubuntu-web/text-colors.cssl` library, and it accepts two parameters
. This library can be used like this:

    @import 'ubuntu-web/text-colors.cssl';
    
    -all {
        -ubuntu-web: text-colors #333 #FFF;
    }
    
The two colors there will be replaced as parameters in the library. If you
ommit parameters when applying the library, rules for those parameters will
be not applied.


