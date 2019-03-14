#!/usr/bin/env bash

cp ../composer.json data/
cp ../README.md content/_index.md

hugo "$@"