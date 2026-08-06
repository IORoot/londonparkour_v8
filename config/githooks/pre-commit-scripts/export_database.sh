#!/bin/bash
# ╭────────────────────────────────────────────────────────────────────────────╮
# │                                                                            │
# │                             PRE-PUSH Deployment                            │
# │                    This will extract the project database                  │
# │                                                                            │
# ╰────────────────────────────────────────────────────────────────────────────╯

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
	FAIL_MYSQLDUMP=7
	FAIL_DUMP_DB=8
	FAIL_DB_COPY=9
    FAIL_MARIADB_ROOT_USERNAME=10
    FAIL_MARIADB_ROOT_PASSWORD=11
    FAIL_MARIADB_DATABASE_NAME=12
    FAIL_MARIADB_CONTAINER_REF=13
}



# ╭──────────────────────────────────────────────────────────╮
# │           Source any Variables in an .env file           │
# ╰──────────────────────────────────────────────────────────╯
function load_env()
{

    ENV_FILE=$1

    if [ ! -z "${ENV_FILE}" ] && [ -f "$ENV_FILE" ]; then
		set -o allexport; source $ENV_FILE; set +o allexport
	fi

	if [ -f .env ]; then
		set -o allexport; source .env; set +o allexport
	fi

    # Do not run if variable set.
    if [ ! -z "${DISABLE_GIT_PRE_COMMIT}" ]; then
        printf "\n${TEXT_AMBER_500}Skipping PRE-COMMIT hook.${RESET_TEXT} Found ${TEXT_TEAL_500}DISABLE_GIT_PRE_COMMIT${RESET_TEXT} variable set.\n\n"
        exit 0
    fi

    if [[ ! -z "${MARIADB_ROOT_USERNAME}" ]] && [[ ! -z "${MARIADB_ROOT_PASSWORD}" ]] && [[ ! -z "${MARIADB_DATABASE_NAME}" ]] && [[ ! -z "${MARIADB_CONTAINER_REF}" ]] ; then
        return
    fi

    cat <<EOF

	⛔️ No .env file found!

	The following variables need to be set in the .env file:

	- MARIADB_ROOT_USERNAME
	- MARIADB_ROOT_PASSWORD
	- MARIADB_DATABASE_NAME
	- MARIADB_CONTAINER_REF

EOF
    exit $FAIL_ENV

}



# ╭──────────────────────────────────────────────────────────╮
# │                        VARIABLES                         │
# ╰──────────────────────────────────────────────────────────╯

function set_filename()
{
    DUMP_FILENAME="backup.sql"
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



# ╭──────────────────────────────────────────────────────────╮
# │                     EXTRACT DATABASE                     │
# ╰──────────────────────────────────────────────────────────╯
function extract_database()
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
    if ! docker ps -a | grep "$MARIADB_CONTAINER_REF" > /dev/null 2>&1; 
    then
        printf "\n${TEXT_RED_500}Error: Container $MARIADB_CONTAINER_REF does not exist"
        exit $FAIL_CONTAINER_EXISTS
    fi


	# Check docker container has mysqldump
    if ! docker exec $MARIADB_CONTAINER_REF \
        mysqldump --version > /dev/null 2>&1; 
    then
        printf "\n${TEXT_RED_500}Error: Container $MARIADB_CONTAINER_REF does not have a mysqldump command"
        exit $FAIL_MYSQLDUMP
    fi

	# Dump Database to /tmp
    if ! docker exec $MARIADB_CONTAINER_REF \
        mysqldump --add-drop-database -u$MARIADB_ROOT_USERNAME -p$MARIADB_ROOT_PASSWORD --single-transaction --databases $MARIADB_DATABASE_NAME > ./database/${DUMP_FILENAME};
    then
        printf "\n${TEXT_RED_500}Error: Failed to dump database to ./database/${DUMP_FILENAME}"
        printf "\n${TEXT_GRAY_500}Check .env: MARIADB_ROOT_USERNAME must be root (not the wordpress app user)."
        exit $FAIL_DUMP_DB
	else
		printf "\n%s  ${TEXT_EMERALD_500}%s${RESET_TEXT}" ${ICON_TICK} "Database dumped into ./database folder."
    fi


	printf "\n\n"
}

function add_to_git_staging()
{
    git add ./database/${DUMP_FILENAME}
}


# ╭──────────────────────────────────────────────────────────╮
# │                        MAIN CODE                         │
# ╰──────────────────────────────────────────────────────────╯
function main()
{
	printf "\n"
	printf "%s  %s \n" "${ICON_BARREL}" "Extracting Database"
	extract_database

    add_to_git_staging
}


stylesheet
fail_codes
load_env "$@"
set_filename
main