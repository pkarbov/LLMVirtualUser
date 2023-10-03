#!/bin/bash

sudo -u nginx php /var/www/nextcloud/latest/occ files:scan --path="/admin/files"