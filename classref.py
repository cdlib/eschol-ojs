#!/bin/python3

import sys
import re

codefiles = [l.strip() for l in open(sys.argv[1])]

funcs = dict()
staticcalls = set()

for cf in codefiles:
    curclass = None
    incomment = False
    for line in open(cf, 'r'):
        if m := re.match(r'\s*class[^\w]+([\w_]+)', line):
            curclass = m.group(1)
            #print(f"class: {curclass}")
        if m := re.search(r'\bfunction[^\w]+([\w_]+)', line):
            funcname = m.group(1)
            fullname = f"{curclass}::{funcname}"
            if funcname == curclass:
                print(f"Warning: probable old-style constructor {fullname}")
            funcs[fullname.lower()] = 'static' if re.search('static', line) else 'regular'
        if '/*' in line:
            incomment = True
        if '*/' in line:
            incomment = False
        if m := re.search(r'\b([\w_]+::[\w_]+)\s*\(', line):
            if (not '@see' in line and not re.match(r'^\s*#',line) 
                    and not re.match(r'^\s*//',line)
                    and not incomment
                    and not re.search(r'parent::|self::|static::', line)):
                staticcalls.add(m.group(1))

# Fix old-style constructors
for cf in codefiles:
    curclass = None
    newcode = []
    nchanges = 0
    for line in open(cf, 'r'):
        if m := re.match(r'\s*class[^\w]+([\w_]+)', line):
            curclass = m.group(1)
        elif m := re.search(r'\bfunction[^\w]+([\w_]+)', line):
            funcname = m.group(1)
            fullname = f"{curclass}::{funcname}"
            if funcname == curclass:
                print(f"Fixing old-style constructor {fullname}")
                line = re.sub(r'\b(function[^\w]+)([\w_]+)', 'function __construct', line)
                nchanges += 1
        newcode.append(line)
    if nchanges > 0:
        with open(cf, 'w') as io:
            for line in newcode:
                io.write(line)

statictofix = set()
for c in sorted(list(staticcalls)):
    if not c.lower() in funcs:
        pass # print(f"hmm, static call to missing func {c}")
    elif funcs[c.lower()] != 'static':
        statictofix.add(c.lower())

# Fix static functions
for cf in codefiles:
    curclass = None
    newcode = []
    nchanges = 0
    for line in open(cf, 'r'):
        if m := re.match(r'\s*class[^\w]+([\w_]+)', line):
            curclass = m.group(1)
        elif m := re.search(r'\bfunction[^\w]+([\w_]+)', line):
            funcname = m.group(1)
            fullname = f"{curclass}::{funcname}"
            if fullname.lower() in statictofix and not('static' in line):
                print(f"Fixing should-be-static function {fullname}")
                line = re.sub(r'\bfunction\b', 'static function', line)
                nchanges += 1
        newcode.append(line)
    if nchanges > 0:
        with open(cf, 'w') as io:
            for line in newcode:
                io.write(line)

# for c in sorted(list(staticcalls)):
#     if not c.lower() in funcs:
#         print(f"hmm, static call to missing func {c}")
#     elif funcs[c.lower()] != 'static':
#         print(f"Warning: static call to non-static func {c}")