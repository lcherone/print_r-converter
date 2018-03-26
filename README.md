# Print_r Converter

The print_r converter is a tool that converts PHP's `print_r()` output back in to PHP code.

An online demo can be found here: [https://print-r-converter-199219.appspot.com/](https://print-r-converter-199219.appspot.com/)

**Screen:**

![Screen](https://i.imgur.com/PQ4eCqC.gif)

**Forked changes:**

 - Changed UI to use VueJS and localstorage instead of cookies.
 - Changed `array()` to short `[]`.
 - Fixed trailing `,` on final array.
 - Added class@anonymous, which casts to stdClass
 - Added closure, which casts to stdClass
