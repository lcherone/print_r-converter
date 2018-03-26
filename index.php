<?php
/*
 * print_r converter - convert print_r() to php variable code
 *
 * Copyright (C) 2011, 2012, 2013 hakre <http://hakre.wordpress.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author hakre <http://hakre.wordpress.com>
 * @license AGPL-3.0 <http://spdx.org/licenses/AGPL-3.0>
 *
 * known:
 *   - string values over multiple lines not supported.
 *   - limited support for objects, stdClass only.
 *
 * CHANGES:
 *
 * 0.1.5 - move into github repository
 * 0.1.4 - remove codepad viper specific stuff
 * 0.1.3 - ignore leading whitespace at the beginning of the string
 * 0.1.2 - experimental compacted output (join numeric indezies)
 * 0.1.1 - allow more whitespace in array-open.
 *       - remove , at the end of values.
 * 0.1.0 - version 0.1.0, fixed some minor issues.
 * 0.0.9 - support for stdClass objects.
 * 0.0.8 - form was closed too early, fixed.
 * 0.0.7 - textarea for output.
 *       - clear / undo clear.
 * 0.0.6 - deal with empty values in parser via state.
 * 0.0.5 - button tooltips.
 *       - input sanitization now upfront.
 *       - html and css updates.
 *       - change output variable-name from $var to $array
 * 0.0.3 - github link opened in frameset eventually, fixed.
 * 0.0.2 - tokenizer less-strict whitespace dealing for array open and close
 *       - cache last input value into cookie
 *       - typo in tokenizer class name, fixed
 *
 * @version 0.1.5
 * @date 2013-09-30
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST = (array) json_decode(file_get_contents('php://input'), true);

    $input = isset($_POST['input'])? (string) $_POST['input'] : '';

    if (($ilen = strlen($input)) > $imaxlen = 8192) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(sprintf('Eek! Maximum allowed input length of %d bytes exceeded: %d! Reduce input size and try again.', $imaxlen, $ilen)));
    }

    // has input buffer
    $buffer = '';
    if ($input) {
        $buffer = str_replace("\r\n", "\n", $input);

        $libpath = __DIR__ . '/lib';
        require($libpath . '/PrintrTokenizer.php');
        require($libpath . '/PrintrParser.php');

        $var = PrintrParser($buffer);

        if (is_array($var)) {
            require($libpath . '/StringLines.php');
            require($libpath . '/ArrayExportObject.php');
            require($libpath . '/ArrayExporter.php');

            $exporter = new ArrayExporter();
            $buffer = $exporter->export($var);
        } else {
            $buffer = var_export($var, true);
        }

        $buffer = str_replace('array (', 'array(', $buffer);
        $buffer = str_replace('stdClass::__set_state(array(', '(object) (array(', $buffer);
        $buffer = preg_replace('~class@anonymous([\s\S])+::__set_state\(array\(~', '(object) (array(', $buffer);
        $buffer = preg_replace('~(=> )\n\s*(array\()~', '$1$2', $buffer);
        $buffer = str_replace("(object) (array(\n)),", '(object) [],', $buffer);
        $buffer = '$'.(is_array($var) ? 'data' : 'object').' = '.$buffer.';';
        $buffer = str_replace(",\n];", "\n];", $buffer);
    }

    header('Content-Type: application/json; charset=utf-8');
    die(json_encode($buffer));
}
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
    <head>
        <title>print_r converter</title>

        <meta name="description" content="Quickly convert print_r() output back into PHP code." />
        <meta name="keywords" content="PHP,print_r,converter,tool,debug,fixer,fix,replace" />
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <meta name="viewport" content="initial-scale=1.0" />
        <meta name="Robots" content="NOODP" />
        <meta name="revisit" content="15 days" />
        <meta name="revisit-after" content="15 days" />
        <meta name="robots" content="index, follow" />
        <meta name="alexa" content="100" />
        <meta name="robots" content="all, index, follow" />
        <meta name="googlebot" content="all, index, follow" />

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.6.2/css/bulma.min.css" integrity="sha256-2k1KVsNPRXxZOsXQ8aqcZ9GOOwmJTMoOB5o5Qp1d6/s=" crossorigin="anonymous" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.32.0/codemirror.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.32.0/theme/ttcn.min.css" rel="stylesheet">
        <style>
            @import url('https://fonts.googleapis.com/css?family=Source+Sans+Pro');
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0
            }
            body { 
                font-family: 'Source Sans Pro', sans-serif; 
                background:
                    radial-gradient(
                        ellipse at top left,
                        rgba(255, 255, 255, 1) 40%,
                        rgba(229, 229, 229, .9) 100%
                    )
            }
            .vue-codemirror {
                width:100%
            }
            .CodeMirror {
                font-family: monospace;
                height: calc(100vh - 250px);
                color: #000;
                direction: ltr
            }
            .content p:not(:last-child) {
                margin-bottom: 0
            }
            .nopadding {
                padding: 0
            }
            .container {
                height: 100vh;
                width: 100vw;
                padding:10px 10px 0 10px
            }
            .button-convert {
                margin-top:10px
            }
            .panel-heading {
                background-color: #e0e5e8
            }
            .footer-text {
                margin-top:-5px
            }
        </style>
    </head>
    <body>
        <div class="container" id="app">
            <div class="content">
                <h1 class="title is-1 is-spaced">print_r() converter...
                    <div class="buttons has-addons is-pulled-right">
                        <button class="button is-large button-convert" @click="setState('input')">Input</button>
                        <button class="button is-success is-large button-convert" @click="setState('output')" :disabled="input === ''">Output</button>
                    </div>
                </h1>
                <h3 class="subtitle is-5">
                    This tool is able to quickly convert <code>print_r()</code> output back into PHP code.
                </h3>
                <nav class="panel" v-if="state === 'input'">
                    <p class="panel-heading">
                        Input 
                    </p>
                    <div class="panel-block nopadding">
                        <codemirror v-model="input" :options="cmOption" @input="store"></codemirror>
                    </div>
                </nav>
                <nav class="panel" v-if="state === 'output'">
                    <p class="panel-heading">
                        Output
                    </p>
                    <div class="panel-block nopadding">
                        <codemirror v-model="output" :options="cmOption"></codemirror>
                    </div>
                </nav>
                <p class="footer-text has-text-right">
                    Fork from <a href="https://github.com/hakre/print_r-converter" target="_blank" rel="noopener">hakre/print_r-converter</a>.
                </p>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.min.js" integrity="sha256-mpnrJ5DpEZZkwkE1ZgkEQQJW/46CSEh/STrZKOB/qoM=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.32.0/codemirror.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/vue"></script>
        <script src="https://cdn.jsdelivr.net/npm/vue-codemirror@4.0.0/dist/vue-codemirror.js"></script>
        <script>
            Vue.use(VueCodemirror)
            new Vue({
                el: '#app',
                data: {
                    state: 'input',
                    input: '',
                    output: '',
                    cmOption: {
                        tabSize: 4,
                        lineNumbers: true,
                        theme: "ttcn"
                    }
                },
                components: {
                    LocalCodemirror: VueCodemirror.codemirror
                },
                methods: {
                    setState (state) {
                        if (state === 'output') {
                            this.convert()
                        }
                        this.state = state
                    },
                    store () {
                        localStorage.setItem('input', JSON.stringify(this.input));
                    },
                    convert () {
                        if (this.input !== '') {
                            axios.post('/', {
                                input: this.input
                            }).then((response) => {
                                this.output = response.data
                            }).catch((error) => {
                                console.log(error);
                            });
                        }
                    }
                },
                mounted() {
                    this.input = JSON.parse(localStorage.getItem('input'));
                }
            })
        </script>
        <script type="text/javascript" src="//platform-api.sharethis.com/js/sharethis.js#property=5ab959f11fff98001395a572&product=sticky-share-buttons"></script>
    </body>
</html>
