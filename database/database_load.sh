#!/bin/bash

set -o allexport; source .env ; set +o allexport

# ╭──────────────────────────────────────────────────────────╮
# │                        FAIL CODES                        │
# ╰──────────────────────────────────────────────────────────╯
function fail_codes()
{
    FAIL_ENV=1
    FAIL_CONTAINER=2
    FAIL_USERNAME=3
    FAIL_PASSWORD=4
    FAIL_DATABASE=5
	FAIL_CONTAINER_EXISTS=6
    FAIL_NO_SQL_FILE=7
}

# ╭──────────────────────────────────────────────────────────╮
# │                    COLOURS AND STYLES                    │
# ╰──────────────────────────────────────────────────────────╯
function stylesheet()
{
    TEXT_WHITE_FFF='\e[38;2;255;255;255m'
    TEXT_STONE_500='\e[38;2;120;113;108m'
    TEXT_STONE_600='\e[38;2;87;83;78m'
    TEXT_ORANGE_500='\e[38;2;249;115;22m'
    TEXT_YELLOW_500='\e[38;2;234;179;8m'
    TEXT_GRAY_200='\e[38;2;229;231;235m'
    TEXT_GRAY_400='\e[38;2;156;163;175m'
    TEXT_GRAY_500='\e[38;2;107;114;128m'
    TEXT_GRAY_600='\e[38;2;75;85;99m'
    TEXT_GRAY_700='\e[38;2;55;65;81m'
    TEXT_VIOLET_500='\e[38;2;139;92;246m'
    TEXT_RED_500='\e[38;2;239;68;68m'
    TEXT_GREEN_500='\e[38;2;34;197;94m'
    TEXT_EMERALD_500='\e[38;2;16;185;129m'
    TEXT_TEAL_500='\e[38;2;20;184;166m'
    TEXT_SKY_500='\e[38;2;14;165;233m'
    TEXT_AMBER_500='\e[38;2;245;158;11m'
    RESET_TEXT='\e[39m'
    RESET_ALL='\e[0m\e[39m\e[49m\033[0m\033[K'
    ICON_FADE_200=░
    ICON_CIRCLE=●
    ICON_TICK=✅
    ICON_CROSS=❌
	ICON_BARREL=🛢
}

function copy_sql_into_container()
{
    if ! docker cp ${SQL_PATH} ${MARIADB_CONTAINER_REF}:/tmp/${SQL_FILE} ;
    then
        printf "%s\t${TEXT_RED_500}%-60s %s\n" "🛢" "Failed to copy SQL file into container" "❌"
        exit 1
    else
        printf "%s\t%-60s %s\n" "🛢" "SQL file copied into container" "✅"
    fi
}


function load_sql_into_database()
{

    if ! docker exec $MARIADB_CONTAINER_REF \
        /bin/sh -c "cat /tmp/${SQL_FILE} | /usr/bin/mysql -u$MARIADB_ROOT_USERNAME -p$MARIADB_ROOT_PASSWORD > /dev/null 2>&1" ;
    then
        printf "%s\t${TEXT_RED_500}%-60s %s\n" "🛢" "Failed to load SQL file into database" "❌"
        exit 1
    else
        printf "%s\t%-60s %s\n" "🛢" "SQL file loaded into database" "✅"
    fi
}

function get_sql_file()
{

    SQL_FILE="backup.sql"
    SQL_PATH="./database/${SQL_FILE}"

    if [ ! -f "$SQL_PATH" ]; then
        printf "%s\t${TEXT_RED_500}%-60s %s\n" "🛢" "Error: SQL file $SQL_PATH does not exist" "❌"
        exit $FAIL_NO_SQL_FILE
    fi
}

function main()
{
    if [ -z "$MARIADB_CONTAINER_REF" ]; then
        printf "\n${TEXT_RED_500}Error: MARIADB_CONTAINER_REF variable not set in .env"
        exit $FAIL_CONTAINER
    fi

	if [ -z "$MARIADB_ROOT_USERNAME" ]; then
        printf "\n${TEXT_RED_500}Error: MARIADB_ROOT_USERNAME variable not set in .env"
        exit $FAIL_USERNAME
    fi

	if [ -z "$MARIADB_ROOT_PASSWORD" ]; then
        printf "\n${TEXT_RED_500}Error: MARIADB_ROOT_PASSWORD variable not set in .env"
        exit $FAIL_PASSWORD
    fi

	if [ -z "$MARIADB_DATABASE_NAME" ]; then
        printf "\n${TEXT_RED_500}Error: MARIADB_DATABASE_NAME variable not set in .env"
        exit $FAIL_DATABASE
    fi


	# Check docker container exists
    if ! docker ps -a | grep "$MARIADB_CONTAINER_REF" > /dev/null 2>&1; then
        printf "%s\t${TEXT_RED_500}%-60s %s\n" "🛢" "Error: Container $MARIADB_CONTAINER_REF does not exist" "❌"
        exit $FAIL_CONTAINER_EXISTS
    fi

    copy_sql_into_container
    load_sql_into_database
}


stylesheet
fail_codes
get_sql_file
main