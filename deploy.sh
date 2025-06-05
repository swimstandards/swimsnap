#!/bin/bash

# Customize this if needed
prefix="v"
timestamp=$(date +"%Y%m%d-%H%M")
new_tag="${prefix}-${timestamp}"

# Delete previous deploy tag if needed (optional: keep history if not needed)
last_tag=$(git tag --sort=-creatordate | head -n 1)
if [[ $last_tag == $prefix-* ]]; then
  echo "Removing old tag: $last_tag"
  git tag -d "$last_tag" >/dev/null 2>&1
  git push origin ":refs/tags/$last_tag" >/dev/null 2>&1
fi

# Create and push new tag
echo "Creating and pushing new tag: $new_tag"
git tag "$new_tag"
git push origin "$new_tag"
