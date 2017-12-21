#!/bin/bash
if [[ -s //etc/profile ]]; then
  source //etc/profile
fi

if [[ -s $HOME/.bash_profile ]] ; then
  source $HOME/.bash_profile
fi

echo "source $HOME/.travis/job_stages" >> $HOME/.bashrc

mkdir -p $HOME/.travis

cat <<'EOFUNC' >>$HOME/.travis/job_stages
ANSI_RED="\033[31;1m"
ANSI_GREEN="\033[32;1m"
ANSI_YELLOW="\033[33;1m"
ANSI_RESET="\033[0m"
ANSI_CLEAR="\033[0K"

if [ $TERM = dumb ]; then
  unset TERM
fi
: "${SHELL:=/bin/bash}"
: "${TERM:=xterm}"
: "${USER:=travis}"
export SHELL
export TERM
export USER

TRAVIS_TEST_RESULT=
TRAVIS_CMD=

TRAVIS_TMPDIR=$(mktemp -d 2>/dev/null || mktemp -d -t 'travis_tmp')
pgrep -u $USER | grep -v -w $$ > $TRAVIS_TMPDIR/pids_before

travis_cmd() {
  local assert output display retry timing cmd result secure

  cmd=$1
  TRAVIS_CMD=$cmd
  shift

  while true; do
    case "$1" in
      --assert)  assert=true; shift ;;
      --echo)    output=true; shift ;;
      --display) display=$2;  shift 2;;
      --retry)   retry=true;  shift ;;
      --timing)  timing=true; shift ;;
      --secure)  secure=" 2>/dev/null"; shift ;;
      *) break ;;
    esac
  done

  if [[ -n "$timing" ]]; then
    travis_time_start
  fi

  if [[ -n "$output" ]]; then
    echo "\$ ${display:-$cmd}"
  fi

  if [[ -n "$retry" ]]; then
    travis_retry eval "$cmd $secure"
    result=$?
  else
    if [[ -n "$secure" ]]; then
      eval "$cmd $secure" 2>/dev/null
    else
      eval "$cmd $secure"
    fi
    result=$?
    if [[ -n $secure && $result -ne 0 ]]; then
      echo -e "${ANSI_RED}The previous command failed, possibly due to a malformed secure environment variable.${ANSI_CLEAR}
${ANSI_RED}Please be sure to escape special characters such as ' ' and '$'.${ANSI_CLEAR}
${ANSI_RED}For more information, see https://docs.travis-ci.com/user/encryption-keys.${ANSI_CLEAR}"
    fi
  fi

  if [[ -n "$timing" ]]; then
    travis_time_finish
  fi

  if [[ -n "$assert" ]]; then
    travis_assert $result
  fi

  return $result
}

travis_time_start() {
  travis_timer_id=$(printf %08x $(( RANDOM * RANDOM )))
  travis_start_time=$(travis_nanoseconds)
  echo -en "travis_time:start:$travis_timer_id\r${ANSI_CLEAR}"
}

travis_time_finish() {
  local result=$?
  travis_end_time=$(travis_nanoseconds)
  local duration=$(($travis_end_time-$travis_start_time))
  echo -en "\ntravis_time:end:$travis_timer_id:start=$travis_start_time,finish=$travis_end_time,duration=$duration\r${ANSI_CLEAR}"
  return $result
}

travis_nanoseconds() {
  local cmd="date"
  local format="+%s%N"
  local os=$(uname)

  if hash gdate > /dev/null 2>&1; then
    
    cmd="gdate"
  elif [[ "$os" = Darwin ]]; then
    
    format="+%s000000000"
  fi

  $cmd -u $format
}

travis_internal_ruby() {
  if ! type rvm &>/dev/null; then
    source $HOME/.rvm/scripts/rvm &>/dev/null
  fi
  local i selected_ruby rubies_array rubies_array_sorted rubies_array_len
  rubies_array=( $(
    rvm list strings \
      | while read -r v; do
          if [[ ! "${v}" =~ ^ruby-(2\.[0-2]\.[0-9]|1\.9\.3) ]]; then
            continue
          fi
          v="${v//ruby-/}"
          v="${v%%-*}"
          echo "$(vers2int "${v}")_${v}"
        done
  ) )
  bash_qsort_numeric "${rubies_array[@]}"
  rubies_array_sorted=( ${bash_qsort_numeric_ret[@]} )
  rubies_array_len="${#rubies_array_sorted[@]}"
  if (( rubies_array_len <= 0 )); then
    echo "default"
  else
    i=$(( rubies_array_len - 1 ))
    selected_ruby="${rubies_array_sorted[${i}]}"
    selected_ruby="${selected_ruby##*_}"
    echo "${selected_ruby:-default}"
  fi
}

travis_assert() {
  local result=${1:-$?}
  if [ $result -ne 0 ]; then
    echo -e "\n${ANSI_RED}The command \"$TRAVIS_CMD\" failed and exited with $result during $TRAVIS_STAGE.${ANSI_RESET}\n\nYour build has been stopped."
    travis_terminate 2
  fi
}

travis_result() {
  local result=$1
  export TRAVIS_TEST_RESULT=$(( ${TRAVIS_TEST_RESULT:-0} | $(($result != 0)) ))

  if [ $result -eq 0 ]; then
    echo -e "\n${ANSI_GREEN}The command \"$TRAVIS_CMD\" exited with $result.${ANSI_RESET}"
  else
    echo -e "\n${ANSI_RED}The command \"$TRAVIS_CMD\" exited with $result.${ANSI_RESET}"
  fi
}

travis_terminate() {
  set +e
  # Restoring the file descriptors of redirect_io filter strategy
  [[ "$TRAVIS_FILTERED" = redirect_io && -e /dev/fd/9 ]] \
      && sync \
      && command exec 1>&9 2>&9 9>&- \
      && sync
  pgrep -u $USER | grep -v -w $$ > $TRAVIS_TMPDIR/pids_after
  kill $(awk 'NR==FNR{a[$1]++;next};!($1 in a)' $TRAVIS_TMPDIR/pids_{before,after}) &> /dev/null || true
  pkill -9 -P $$ &> /dev/null || true
  exit $1
}

travis_wait() {
  local timeout=$1

  if [[ $timeout =~ ^[0-9]+$ ]]; then
    
    shift
  else
    
    timeout=20
  fi

  local cmd="$@"
  local log_file=travis_wait_$$.log

  $cmd &>$log_file &
  local cmd_pid=$!

  travis_jigger $! $timeout $cmd &
  local jigger_pid=$!
  local result

  {
    wait $cmd_pid 2>/dev/null
    result=$?
    ps -p$jigger_pid &>/dev/null && kill $jigger_pid
  }

  if [ $result -eq 0 ]; then
    echo -e "\n${ANSI_GREEN}The command $cmd exited with $result.${ANSI_RESET}"
  else
    echo -e "\n${ANSI_RED}The command $cmd exited with $result.${ANSI_RESET}"
  fi

  echo -e "\n${ANSI_GREEN}Log:${ANSI_RESET}\n"
  cat $log_file

  return $result
}

travis_jigger() {
  
  local cmd_pid=$1
  shift
  local timeout=$1 
  shift
  local count=0

  
  echo -e "\n"

  while [ $count -lt $timeout ]; do
    count=$(($count + 1))
    echo -ne "Still running ($count of $timeout): $@\r"
    sleep 60
  done

  echo -e "\n${ANSI_RED}Timeout (${timeout} minutes) reached. Terminating \"$@\"${ANSI_RESET}\n"
  kill -9 $cmd_pid
}

travis_retry() {
  local result=0
  local count=1
  while [ $count -le 3 ]; do
    [ $result -ne 0 ] && {
      echo -e "\n${ANSI_RED}The command \"$@\" failed. Retrying, $count of 3.${ANSI_RESET}\n" >&2
    }
    "$@" && { result=0 && break; } || result=$?
    count=$(($count + 1))
    sleep 1
  done

  [ $count -gt 3 ] && {
    echo -e "\n${ANSI_RED}The command \"$@\" failed 3 times.${ANSI_RESET}\n" >&2
  }

  return $result
}

travis_fold() {
  local action=$1
  local name=$2
  echo -en "travis_fold:${action}:${name}\r${ANSI_CLEAR}"
}

decrypt() {
  echo $1 | base64 -d | openssl rsautl -decrypt -inkey $HOME/.ssh/id_rsa.repo
}

vers2int() {
  printf '1%03d%03d%03d%03d' $(echo "$1" | tr '.' ' ')
}

bash_qsort_numeric() {
   local pivot i smaller=() larger=()
   bash_qsort_numeric_ret=()
   (($#==0)) && return 0
   pivot=${1}
   shift
   for i; do
      if [[ ${i%%_*} -lt ${pivot%%_*} ]]; then
         smaller+=( "$i" )
      else
         larger+=( "$i" )
      fi
   done
   bash_qsort_numeric "${smaller[@]}"
   smaller=( "${bash_qsort_numeric_ret[@]}" )
   bash_qsort_numeric "${larger[@]}"
   larger=( "${bash_qsort_numeric_ret[@]}" )
   bash_qsort_numeric_ret=( "${smaller[@]}" "$pivot" "${larger[@]}" )
}

EOFUNC


if [[ -f /etc/apt/sources.list.d/rabbitmq-source.list ]] ; then
  sudo rm -f /etc/apt/sources.list.d/rabbitmq-source.list
fi


if [[ -f /etc/apt/sources.list.d/neo4j.list ]] ; then
  sudo rm -f /etc/apt/sources.list.d/neo4j.list
fi

mkdir -p $HOME/build
cd       $HOME/build

# START_FUNCS
cat <<'EOFUNC_SETUP_FILTER' >>$HOME/.travis/job_stages
function travis_run_setup_filter() {
for dir in $(echo $PATH | tr : " "); do
  test -d $dir && sudo chmod -vv o-w $dir | grep changed
done

:
}

EOFUNC_SETUP_FILTER
cat <<'EOFUNC_CONFIGURE' >>$HOME/.travis/job_stages
function travis_run_configure() {

travis_fold start system_info
  echo -e "\033[33;1mBuild system information\033[0m"
  echo -e "Build language: php"
  echo -e "Build id: ''"
  echo -e "Job id: ''"
  echo -e "Runtime kernel version: $(uname -r)"
  if [[ -f /usr/share/travis/system_info ]]; then
    cat /usr/share/travis/system_info
  fi
travis_fold end system_info

echo
          if [[ -d /var/lib/apt/lists && -n $(command -v apt-get) ]]; then
            grep -l -i -r basho /etc/apt/sources.list.d | xargs sudo rm -vf
          fi

          if [[ -d /var/lib/apt/lists && -n $(command -v apt-get) ]]; then
            for f in $(grep -l rwky/redis /etc/apt/sources.list.d/*); do
              sed 's,rwky/redis,rwky/ppa,g' $f > /tmp/${f##**/}
              sudo mv /tmp/${f##**/} /etc/apt/sources.list.d
            done
          fi

if [[ $(command -v lsb_release) ]]; then
  travis_cmd sudo\ apt-key\ adv\ --keyserver\ hkp://keyserver.ubuntu.com:80\ --recv\ EA312927
fi

            if command -v lsb_release; then
              grep -l -i -r hhvm /etc/apt/sources.list.d | xargs sudo rm -f
              sudo sed -i /hhvm/d /etc/apt/sources.list
              if [[ $(lsb_release -cs) = trusty ]]; then
                sudo apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xB4112585D386EB94
                sudo add-apt-repository "deb [arch=amd64] https://dl.hhvm.com/ubuntu $(lsb_release -sc) main"
              fi
            fi &>/dev/null

          if [[ -d /var/lib/apt/lists && -n $(command -v apt-get) ]]; then
            sudo rm -rf /var/lib/apt/lists/*
            sudo apt-get update -qq 2>&1 >/dev/null
          fi

if [[ $(uname) = Linux ]]; then
  if [[ $(lsb_release -sc 2>/dev/null) = trusty ]]; then
    unset _JAVA_OPTIONS
    unset MALLOC_ARENA_MAX
  fi
fi

export PATH=$(echo $PATH | sed -e 's/::/:/g')
export PATH=$(echo -n $PATH | perl -e 'print join(":", grep { not $seen{$_}++ } split(/:/, scalar <>))')
echo "options rotate
options timeout:1

nameserver 8.8.8.8
nameserver 8.8.4.4
nameserver 208.67.222.222
nameserver 208.67.220.220
" | sudo tee /etc/resolv.conf &> /dev/null
sudo sed -e 's/^\(127\.0\.0\.1.*\)$/\1 '`hostname`'/' -i'.bak' /etc/hosts
test -f ~/.m2/settings.xml && sed -i.bak -e 's|https://nexus.codehaus.org/snapshots/|https://oss.sonatype.org/content/repositories/codehaus-snapshots/|g' ~/.m2/settings.xml
sed -e 's/^\([0-9a-f:]\+\s\)localhost/\1/' /etc/hosts > /tmp/hosts.tmp && cat /tmp/hosts.tmp | sudo tee /etc/hosts
test -f /etc/mavenrc && sudo sed -e 's/M2_HOME=\(.\+\)$/M2_HOME=${M2_HOME:-\1}/' -i'.bak' /etc/mavenrc
if [ $(command -v sw_vers) ]; then
  echo "Fix WWDRCA Certificate"
  sudo security delete-certificate -Z 0950B6CD3D2F37EA246A1AAA20DFAADBD6FE1F75 /Library/Keychains/System.keychain
  wget -q https://developer.apple.com/certificationauthority/AppleWWDRCA.cer
  sudo security add-certificates -k /Library/Keychains/System.keychain AppleWWDRCA.cer
fi

grep '^127\.0\.0\.1' /etc/hosts | sed -e 's/^127\.0\.0\.1 \(.*\)/\1/g' | sed -e 's/localhost \(.*\)/\1/g' | tr "\n" " " > /tmp/hosts_127_0_0_1
sed '/^127\.0\.0\.1/d' /etc/hosts > /tmp/hosts_sans_127_0_0_1
cat /tmp/hosts_sans_127_0_0_1 | sudo tee /etc/hosts > /dev/null
echo -n "127.0.0.1 localhost " | sudo tee -a /etc/hosts > /dev/null
cat /tmp/hosts_127_0_0_1 | sudo tee -a /etc/hosts > /dev/null
# apply :home_paths
for path_entry in $HOME/.local/bin $HOME/bin ; do
  if [[ ${PATH%%:*} != $path_entry ]] ; then
    export PATH="$path_entry:$PATH"
  fi
done

if [ ! $(uname|grep Darwin) ]; then echo update_initramfs=no | sudo tee -a /etc/initramfs-tools/update-initramfs.conf > /dev/null; fi

if [[ "$(sw_vers -productVersion 2>/dev/null | cut -d . -f 2)" -lt 12 ]]; then
  mkdir -p $HOME/.ssh
  chmod 0700 $HOME/.ssh
  touch $HOME/.ssh/config
  echo -e "Host *
    UseRoaming no
  " | cat - $HOME/.ssh/config > $HOME/.ssh/config.tmp && mv $HOME/.ssh/config.tmp $HOME/.ssh/config
fi

function travis_debug() {
echo -e "\033[31;1mThe debug environment is not available. Please contact support.\033[0m"
false
}

if [[ $(command -v sw_vers) ]]; then
  travis_cmd rvm\ use --echo
fi

if [[ -L /usr/lib/jvm/java-8-oracle-amd64 ]]; then
  echo -e "Removing symlink /usr/lib/jvm/java-8-oracle-amd64"
  travis_cmd sudo\ rm\ -f\ /usr/lib/jvm/java-8-oracle-amd64 --echo
  if [[ -f $HOME/.jdk_switcher_rc ]]; then
    echo -e "Reload jdk_switcher"
    travis_cmd source\ \$HOME/.jdk_switcher_rc --echo
  fi
  if [[ -f /opt/jdk_switcher/jdk_switcher.sh ]]; then
    echo -e "Reload jdk_switcher"
    travis_cmd source\ /opt/jdk_switcher/jdk_switcher.sh --echo
  fi
fi

if [[ $(uname -m) != ppc64le && $(command -v lsb_release) && $(lsb_release -cs) != precise ]]; then
  travis_cmd sudo\ dpkg\ --add-architecture\ i386
fi

cat >$HOME/.rvm/hooks/after_use <<EORVMHOOK
gem --help >&/dev/null || return 0

vers2int() {
  printf '1%03d%03d%03d%03d' \$(echo "\$1" | tr '.' ' ')
}

if [[ \$(vers2int \`gem --version\`) -lt \$(vers2int "2.6.13") ]]; then
  echo ""
  echo "** Updating RubyGems to the latest version for security reasons. **"
  echo "** If you need an older version, you can downgrade with 'gem update --system OLD_VERSION'. **"
  echo ""
  gem update --system
fi
EORVMHOOK

chmod +x $HOME/.rvm/hooks/after_use
pc=$(yarn global bin | grep /)
if [[ -n $pc && ! :$PATH: =~ :$pc: ]]; then export PATH=$PATH:$pc; fi
unset pc

:
}

EOFUNC_CONFIGURE
cat <<'EOFUNC_CHECKOUT' >>$HOME/.travis/job_stages
function travis_run_checkout() {
export GIT_ASKPASS=echo

travis_fold start git.checkout
  if [[ ! -d lucatume/gattiny/.git ]]; then
    travis_cmd git\ clone\ --depth\=50\ --branch\=\'\'\ git@github.com:lucatume/gattiny.git\ lucatume/gattiny --echo --retry --timing
    if [[ $? -ne 0 ]]; then
      echo -e "\033[31;1mFailed to clone from GitHub.\033[0m"
      echo -e "Checking GitHub status (https://status.github.com/api/last-message.json):"
      curl -sL https://status.github.com/api/last-message.json | jq -r .[]
      travis_terminate 1
    fi
  else
    travis_cmd git\ -C\ lucatume/gattiny\ fetch\ origin --assert --echo --retry --timing
    travis_cmd git\ -C\ lucatume/gattiny\ reset\ --hard --assert --echo
  fi
  rm -f $HOME/.netrc
  travis_cmd cd\ lucatume/gattiny --echo
  travis_cmd git\ checkout\ -qf\  --assert --echo
travis_fold end git.checkout

if [[ -f .gitmodules ]]; then
  travis_fold start git.submodule
    echo Host\ github.com'
    '\	StrictHostKeyChecking\ no'
    ' >> ~/.ssh/config
    travis_cmd git\ submodule\ update\ --init\ --recursive --assert --echo --retry --timing
  travis_fold end git.submodule
fi

rm -f ~/.ssh/source_rsa
:
}

EOFUNC_CHECKOUT
cat <<'EOFUNC_PREPARE' >>$HOME/.travis/job_stages
function travis_run_prepare() {

travis_fold start services
  travis_mysql_ping() {
    local i timeout=10
    until (( i++ >= $timeout )) || mysql <<<'select 1;' >&/dev/null; do sleep 1; done
    if (( i > $timeout )); then
      echo -e "${ANSI_RED}MySQL did not start within ${timeout} seconds${ANSI_RESET}"
    fi
    unset -f travis_mysql_ping
  }
  travis_cmd sudo\ service\ mysql\ start --echo --timing
  travis_mysql_ping
  sleep 3
travis_fold end services

export PS4=+

travis_fold start hosts.before
  echo
  cat /etc/hosts
  echo
travis_fold end hosts.before

travis_fold start hosts
  sed -e 's/^\(127\.0\.0\.1.*\)$/\1 wp.localhost/' /etc/hosts > /tmp/hosts
  cat /tmp/hosts | sudo tee /etc/hosts > /dev/null
travis_fold end hosts

travis_fold start hosts.after
  echo
  cat /etc/hosts
  echo
travis_fold end hosts.after

:
}

EOFUNC_PREPARE
cat <<'EOFUNC_DISABLE_SUDO' >>$HOME/.travis/job_stages
function travis_run_disable_sudo() {
:
}

EOFUNC_DISABLE_SUDO
cat <<'EOFUNC_EXPORT' >>$HOME/.travis/job_stages
function travis_run_export() {
export TRAVIS=true
export CI=true
export CONTINUOUS_INTEGRATION=true
export PAGER=cat
export HAS_JOSH_K_SEAL_OF_APPROVAL=true
export TRAVIS_ALLOW_FAILURE=''
export TRAVIS_EVENT_TYPE=''
export TRAVIS_PULL_REQUEST=false
export TRAVIS_SECURE_ENV_VARS=false
export TRAVIS_BUILD_ID=''
export TRAVIS_BUILD_NUMBER=''
export TRAVIS_BUILD_DIR=$HOME/build/lucatume/gattiny
export TRAVIS_JOB_ID=''
export TRAVIS_JOB_NUMBER=''
export TRAVIS_BRANCH=''
export TRAVIS_COMMIT=''
export TRAVIS_COMMIT_MESSAGE=$(git log --format=%B -n 1 | head -c 32768)
export TRAVIS_COMMIT_RANGE=''
export TRAVIS_REPO_SLUG=lucatume/gattiny
export TRAVIS_OS_NAME=''
export TRAVIS_LANGUAGE=php
export TRAVIS_TAG=''
export TRAVIS_SUDO=true
export TRAVIS_PULL_REQUEST_BRANCH=''
export TRAVIS_PULL_REQUEST_SHA=''
export TRAVIS_PULL_REQUEST_SLUG=''
echo
echo -e "\033[33;1mSetting environment variables from .travis.yml\033[0m"
travis_cmd export\ WP_ROOT_FOLDER\=\"/tmp/wordpress\" --echo
travis_cmd export\ WP_URL\=\"http://wp.localhost\" --echo
travis_cmd export\ WP_DOMAIN\=\"wp.localhost\" --echo
travis_cmd export\ DB_NAME\=\"wp\" --echo
travis_cmd export\ TEST_DB_NAME\=\"tests\" --echo
travis_cmd export\ WP_TABLE_PREFIX\=\"wp_\" --echo
travis_cmd export\ WP_ADMIN_USERNAME\=\"admin\" --echo
travis_cmd export\ WP_ADMIN_PASSWORD\=\"admin\" --echo
echo
export TRAVIS_PHP_VERSION=["7.1"]
:
}

EOFUNC_EXPORT
cat <<'EOFUNC_SETUP' >>$HOME/.travis/job_stages
function travis_run_setup() {
travis_cmd phpenv\ global\ \[\"7.1\"\]\ 2\>/dev/null --echo --timing

if [[ $? -ne 0 ]]; then
  echo -e "\033[33;1m["7.1"] is not pre-installed; installing\033[0m"
  if [[ $(uname) = 'Linux' ]]; then
    travis_host_os=$(lsb_release -is | tr 'A-Z' 'a-z')
    travis_rel_version=$(lsb_release -rs)
  elif [[ $(uname) = 'Darwin' ]]; then
    travis_host_os=osx
    travis_rel=$(sw_vers -productVersion)
    travis_rel_version=${travis_rel%*.*}
  fi
  archive_url=https://s3.amazonaws.com/travis-php-archives/binaries/${travis_host_os}/${travis_rel_version}/$(uname -m)/php-["7.1"].tar.bz2
  echo -e "\033[33;1mDownloading archive: ${archive_url}\033[0m"
  travis_cmd curl\ -s\ -o\ archive.tar.bz2\ \$archive_url\ \&\&\ tar\ xjf\ archive.tar.bz2\ --directory\ / --echo --timing
  travis_cmd rm\ -f\ archive.tar.bz2 --assert --timing
else
  travis_fold start pearrc
    echo -e "\033[33;1mWriting $HOME/.pearrc\033[0m"
    travis_cmd echo\ \'\<\?php\ error_reporting\(0\)\;\ echo\ serialize\('
    '\ \ \ \ \ \ \ \ \ \ \ \ \['
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'preferred_state\'\ \=\>\ \"stable\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'temp_dir\'\ \ \ \ \ \=\>\ \"/tmp/pear/install\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'download_dir\'\ \=\>\ \"/tmp/pear/install\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'bin_dir\'\ \ \ \ \ \ \=\>\ \"/home/travis/.phpenv/versions/\[\"7.1\"\]/bin\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'php_dir\'\ \ \ \ \ \ \=\>\ \"/home/travis/.phpenv/versions/\[\"7.1\"\]/share/pear\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'doc_dir\'\ \ \ \ \ \ \=\>\ \"/home/travis/.phpenv/versions/\[\"7.1\"\]/docs\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'data_dir\'\ \ \ \ \ \=\>\ \"/home/travis/.phpenv/versions/\[\"7.1\"\]/data\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'cfg_dir\'\ \ \ \ \ \ \=\>\ \"/home/travis/.phpenv/versions/\[\"7.1\"\]/cfg\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'www_dir\'\ \ \ \ \ \ \=\>\ \"/home/travis/.phpenv/versions/\[\"7.1\"\]/www\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'man_dir\'\ \ \ \ \ \ \=\>\ \"/home/travis/.phpenv/versions/\[\"7.1\"\]/man\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'test_dir\'\ \ \ \ \ \=\>\ \"/home/travis/.phpenv/versions/\[\"7.1\"\]/tests\",'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'__channels\'\ \ \ \=\>\ \['
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \ \ \'__uri\'\ \=\>\ \[\],'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \ \ \'doc.php.net\'\ \=\>\ \[\],'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \ \ \'pecl.php.net\'\ \=\>\ \[\]'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \],'
    '\ \ \ \ \ \ \ \ \ \ \ \ \ \ \'auto_discover\'\ \=\>\ 1'
    '\ \ \ \ \ \ \ \ \ \ \ \ \]'
    '\ \ \ \ \ \ \ \ \ \ \)\ \?\>\'\ \|\ php\ \>\ \$HOME/.pearrc --assert --timing
    travis_cmd pear\ config-show --assert --echo --timing
  travis_fold end pearrc
fi

travis_cmd phpenv\ global\ \[\"7.1\"\] --assert --echo --timing
phpenv rehash

if [[ -n $(composer --version | grep -o "version [^ ]*1\.0-dev") ]]; then
  travis_cmd composer\ self-update\ 1.0.0 --echo --timing
fi

travis_cmd composer\ self-update --echo --timing
:
}

EOFUNC_SETUP
cat <<'EOFUNC_SETUP_CASHER' >>$HOME/.travis/job_stages
function travis_run_setup_casher() {

travis_fold start cache.1
  echo -e "Setting up build cache"
  rvm use $(rvm current 2>/dev/null) >&/dev/null
  travis_cmd export\ CASHER_DIR\=\$HOME/.casher --echo
  mkdir -p $CASHER_DIR/bin
  travis_cmd curl\ -sf\ \ -o\ \$CASHER_DIR/bin/casher\ https:///files/casher --echo --display Installing\ caching\ utilities --retry --timing
  if [[ $? -ne 0 ]]; then
    travis_cmd curl\ -sf\ \ -o\ \$CASHER_DIR/bin/casher\ https://raw.githubusercontent.com/travis-ci/casher/production/bin/casher --echo --display Installing\ caching\ utilities\ from\ the\ Travis\ CI\ server\ \(https:///files/casher\)\ failed,\ failing\ over\ to\ using\ GitHub\ \(https://raw.githubusercontent.com/travis-ci/casher/production/bin/casher\) --retry --timing
  fi
  if [[ $? -ne 0 ]]; then
    echo -e "\033[33;1mFailed to fetch casher from GitHub, disabling cache.\033[0m"
  fi
  if [[ -f $CASHER_DIR/bin/casher ]]; then
    chmod +x $CASHER_DIR/bin/casher
  fi
  if [[ $- = *e* ]]; then
    ERREXIT_SET=true
  fi
  set +e
  if [[ -f $CASHER_DIR/bin/casher ]]; then
    travis_cmd type\ rvm\ \&\>/dev/null\ \|\|\ source\ \~/.rvm/scripts/rvm --timing
    travis_cmd rvm\ \$\(travis_internal_ruby\)\ --fuzzy\ do\ \$CASHER_DIR/bin/casher\ fetch\ https://s3.amazonaws.com/cache_bucket/1234567890//cache-846c7809fe1268c8e960233525d99e9358c17c5d2ab20dc9d3c21354057243f2--php-7.1.tgz\\\?X-Amz-Algorithm\\\=AWS4-HMAC-SHA256\\\&X-Amz-Credential\\\=abcdef0123456789\\\%2F20171221\\\%2Fus-east-1\\\%2Fs3\\\%2Faws4_request\\\&X-Amz-Date\\\=20171221T095914Z\\\&X-Amz-Expires\\\=60\\\&X-Amz-Signature\\\=edca1548b3e90aa109c9af63c9ac443dc1615c2cd02f99400945a46981e6b0fd\\\&X-Amz-SignedHeaders\\\=host\ https://s3.amazonaws.com/cache_bucket/1234567890//cache--php-7.1.tgz\\\?X-Amz-Algorithm\\\=AWS4-HMAC-SHA256\\\&X-Amz-Credential\\\=abcdef0123456789\\\%2F20171221\\\%2Fus-east-1\\\%2Fs3\\\%2Faws4_request\\\&X-Amz-Date\\\=20171221T095914Z\\\&X-Amz-Expires\\\=60\\\&X-Amz-Signature\\\=0dce2da345be4e2af3a16a9fe8362b0f35f23404c5a15255e50fc14155556f09\\\&X-Amz-SignedHeaders\\\=host\ https://s3.amazonaws.com/cache_bucket/1234567890/cache-846c7809fe1268c8e960233525d99e9358c17c5d2ab20dc9d3c21354057243f2--php-7.1.tgz\\\?X-Amz-Algorithm\\\=AWS4-HMAC-SHA256\\\&X-Amz-Credential\\\=abcdef0123456789\\\%2F20171221\\\%2Fus-east-1\\\%2Fs3\\\%2Faws4_request\\\&X-Amz-Date\\\=20171221T095914Z\\\&X-Amz-Expires\\\=60\\\&X-Amz-Signature\\\=75d06b3fbfcdac6d458d8ac7d0f4de2f89e27db68fad5a4c8b96db69944cf9ca\\\&X-Amz-SignedHeaders\\\=host\ https://s3.amazonaws.com/cache_bucket/1234567890/cache--php-7.1.tgz\\\?X-Amz-Algorithm\\\=AWS4-HMAC-SHA256\\\&X-Amz-Credential\\\=abcdef0123456789\\\%2F20171221\\\%2Fus-east-1\\\%2Fs3\\\%2Faws4_request\\\&X-Amz-Date\\\=20171221T095914Z\\\&X-Amz-Expires\\\=60\\\&X-Amz-Signature\\\=136d2d0b46fe808b5eb27188c16ad70a6b62c062f316889a69bd88472491dc51\\\&X-Amz-SignedHeaders\\\=host --timing
  fi
  if [[ -n $ERREXIT_SET ]]; then
    set -e
  fi
  if [[ $- = *e* ]]; then
    ERREXIT_SET=true
  fi
  set +e
  if [[ -f $CASHER_DIR/bin/casher ]]; then
    travis_cmd type\ rvm\ \&\>/dev/null\ \|\|\ source\ \~/.rvm/scripts/rvm --timing
    travis_cmd rvm\ \$\(travis_internal_ruby\)\ --fuzzy\ do\ \$CASHER_DIR/bin/casher\ add\ vendor\ \$HOME/.composer/cache/files --timing
  fi
  if [[ -n $ERREXIT_SET ]]; then
    set -e
  fi
travis_fold end cache.1

:
}

EOFUNC_SETUP_CASHER
cat <<'EOFUNC_SETUP_CACHE' >>$HOME/.travis/job_stages
function travis_run_setup_cache() {
:
}

EOFUNC_SETUP_CACHE
cat <<'EOFUNC_ANNOUNCE' >>$HOME/.travis/job_stages
function travis_run_announce() {
travis_cmd php\ --version --echo
travis_cmd composer\ --version --echo
echo -e "${ANSI_RESET}"
:
}

EOFUNC_ANNOUNCE
cat <<'EOFUNC_DEBUG' >>$HOME/.travis/job_stages
function travis_run_debug() {
:
}

EOFUNC_DEBUG
cat <<'EOFUNC_BEFORE_INSTALL' >>$HOME/.travis/job_stages
function travis_run_before_install() {

travis_fold start before_install.1
  travis_cmd mysql\ -e\ \"create\ database\ IF\ NOT\ EXISTS\ \$DB_NAME\;\"\ -uroot --assert --echo --timing
travis_fold end before_install.1

travis_fold start before_install.2
  travis_cmd mysql\ -e\ \"create\ database\ IF\ NOT\ EXISTS\ \$TEST_DB_NAME\;\"\ -uroot --assert --echo --timing
travis_fold end before_install.2

travis_fold start before_install.3
  travis_cmd mkdir\ -p\ \$WP_ROOT_FOLDER --assert --echo --timing
travis_fold end before_install.3

travis_fold start before_install.4
  travis_cmd mkdir\ tools --assert --echo --timing
travis_fold end before_install.4

travis_fold start before_install.5
  travis_cmd wget\ https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar\ -P\ \$\(pwd\)/tools/ --assert --echo --timing
travis_fold end before_install.5

travis_fold start before_install.6
  travis_cmd chmod\ \+x\ tools/wp-cli.phar\ \&\&\ mv\ tools/wp-cli.phar\ tools/wp --assert --echo --timing
travis_fold end before_install.6

travis_fold start before_install.7
  travis_cmd export\ PATH\=\$PATH:\$\(pwd\)/tools --assert --echo --timing
travis_fold end before_install.7

travis_fold start before_install.8
  travis_cmd export\ PATH\=vendor/bin:\$PATH --assert --echo --timing
travis_fold end before_install.8

travis_fold start before_install.9
  travis_cmd pear\ config-set\ preferred_state\ beta --assert --echo --timing
travis_fold end before_install.9

travis_fold start before_install.10
  travis_cmd pecl\ channel-update\ pecl.php.net --assert --echo --timing
travis_fold end before_install.10

travis_fold start before_install.11
  travis_cmd yes\ \|\ pecl\ install\ imagick --assert --echo --timing
travis_fold end before_install.11

:
}

EOFUNC_BEFORE_INSTALL
cat <<'EOFUNC_INSTALL' >>$HOME/.travis/job_stages
function travis_run_install() {

travis_fold start install.1
  travis_cmd composer\ install --assert --echo --timing
travis_fold end install.1

travis_fold start install.2
  travis_cmd cd\ \$WP_ROOT_FOLDER --assert --echo --timing
travis_fold end install.2

travis_fold start install.3
  travis_cmd wp\ core\ download\ --version\=\$WP_VERSION --assert --echo --timing
travis_fold end install.3

travis_fold start install.4
  travis_cmd wp\ config\ create\ --dbname\=\"\$DB_NAME\"\ --dbuser\=\"root\"\ --dbpass\=\"\"\ --dbhost\=\"127.0.0.1\"\ --dbprefix\=\"\$WP_TABLE_PREFIX\" --assert --echo --timing
travis_fold end install.4

travis_fold start install.5
  travis_cmd sudo\ sed\ -e\ \"s\#\^\\\$table_\#define\(\'WP_DEBUG\',true\)\;\ define\(\'WP_DEBUG_LOG\',true\)\;\ \\\$table_\#g\"\ --in-place\ \$WP_ROOT_FOLDER/wp-config.php --assert --echo --timing
travis_fold end install.5

travis_fold start install.6
  travis_cmd wp\ core\ install\ --url\=\"\$WP_URL\"\ --title\=\"Test\"\ --admin_user\=\"\$WP_ADMIN_USERNAME\"\ --admin_password\=\"\$WP_ADMIN_PASSWORD\"\ --admin_email\=\"admin@\$WP_DOMAIN\"\ --skip-email --assert --echo --timing
travis_fold end install.6

travis_fold start install.7
  travis_cmd wp\ rewrite\ structure\ \'/\%postname\%/\'\ --hard --assert --echo --timing
travis_fold end install.7

travis_fold start install.8
  travis_cmd wp\ core\ update-db --assert --echo --timing
travis_fold end install.8

travis_fold start install.9
  travis_cmd cp\ -r\ \ \$TRAVIS_BUILD_DIR\ \$WP_ROOT_FOLDER/wp-content/plugins/gattiny --assert --echo --timing
travis_fold end install.9

travis_fold start install.10
  travis_cmd ls\ -la\ \$WP_ROOT_FOLDER/wp-content/plugins --assert --echo --timing
travis_fold end install.10

travis_fold start install.11
  travis_cmd wp\ plugin\ deactivate\ \$\(wp\ plugin\ list\ --status\=active\ --field\=name\) --assert --echo --timing
travis_fold end install.11

travis_fold start install.12
  travis_cmd wp\ plugin\ activate\ gattiny --assert --echo --timing
travis_fold end install.12

travis_fold start install.13
  travis_cmd wp\ db\ export\ \$TRAVIS_BUILD_DIR/tests/_data/dump.sql --assert --echo --timing
travis_fold end install.13

travis_fold start install.14
  travis_cmd cd\ \$TRAVIS_BUILD_DIR --assert --echo --timing
travis_fold end install.14

travis_fold start install.15
  travis_cmd sudo\ cp\ tests/travis/nginx.conf\ /etc/nginx/sites-available/\$WP_DOMAIN --assert --echo --timing
travis_fold end install.15

travis_fold start install.16
  travis_cmd sudo\ sed\ -e\ \"s\?\%WP_ROOT_FOLDER\%\?\$WP_ROOT_FOLDER\?g\"\ --in-place\ /etc/nginx/sites-available/\$WP_DOMAIN --assert --echo --timing
travis_fold end install.16

travis_fold start install.17
  travis_cmd sudo\ sed\ -e\ \"s\?\%WP_DOMAIN\%\?\$WP_DOMAIN\?g\"\ --in-place\ /etc/nginx/sites-available/\$WP_DOMAIN --assert --echo --timing
travis_fold end install.17

travis_fold start install.18
  travis_cmd sudo\ ln\ -s\ /etc/nginx/sites-available/\$WP_DOMAIN\ /etc/nginx/sites-enabled/ --assert --echo --timing
travis_fold end install.18

:
}

EOFUNC_INSTALL
cat <<'EOFUNC_BEFORE_SCRIPT' >>$HOME/.travis/job_stages
function travis_run_before_script() {

travis_fold start before_script.1
  travis_cmd sudo\ service\ php7.1-fpm\ stop --assert --echo --timing
travis_fold end before_script.1

travis_fold start before_script.2
  travis_cmd sudo\ chmod\ ugo\+rw\ /etc/php/7.1/fpm/pool.d/www.conf --assert --echo --timing
travis_fold end before_script.2

travis_fold start before_script.3
  travis_cmd echo\ \"catch_workers_output\ \=\ yes\"\ \>\>\ /etc/php/7.1/fpm/pool.d/www.conf --assert --echo --timing
travis_fold end before_script.3

travis_fold start before_script.4
  travis_cmd echo\ \"php_flag\[display_errors\]\ \=\ on\"\ \>\>\ /etc/php/7.1/fpm/pool.d/www.conf --assert --echo --timing
travis_fold end before_script.4

travis_fold start before_script.5
  travis_cmd echo\ \"php_admin_value\[error_log\]\ \=\ /var/log/fpm-php.www.log\"\ \>\>\ /etc/php/7.1/fpm/pool.d/www.conf --assert --echo --timing
travis_fold end before_script.5

travis_fold start before_script.6
  travis_cmd echo\ \"php_admin_flag\[log_errors\]\ \=\ on\"\ \>\>\ /etc/php/7.1/fpm/pool.d/www.conf --assert --echo --timing
travis_fold end before_script.6

travis_fold start before_script.7
  travis_cmd cat\ /etc/php/7.1/fpm/pool.d/www.conf\ \|\ grep\ catch_workers_output --assert --echo --timing
travis_fold end before_script.7

travis_fold start before_script.8
  travis_cmd cat\ /etc/php/7.1/fpm/pool.d/www.conf\ \|\ grep\ display_errors --assert --echo --timing
travis_fold end before_script.8

travis_fold start before_script.9
  travis_cmd cat\ /etc/php/7.1/fpm/pool.d/www.conf\ \|\ grep\ error_log --assert --echo --timing
travis_fold end before_script.9

travis_fold start before_script.10
  travis_cmd cat\ /etc/php/7.1/fpm/pool.d/www.conf\ \|\ grep\ log_errors --assert --echo --timing
travis_fold end before_script.10

travis_fold start before_script.11
  travis_cmd sudo\ service\ php7.1-fpm\ start --assert --echo --timing
travis_fold end before_script.11

travis_fold start before_script.12
  travis_cmd sudo\ service\ nginx\ restart --assert --echo --timing
travis_fold end before_script.12

travis_fold start before_script.13
  travis_cmd curl\ \$WP_URL --assert --echo --timing
travis_fold end before_script.13

travis_fold start before_script.14
  travis_cmd codecept\ build --assert --echo --timing
travis_fold end before_script.14

:
}

EOFUNC_BEFORE_SCRIPT
cat <<'EOFUNC_SCRIPT' >>$HOME/.travis/job_stages
function travis_run_script() {
travis_cmd codecept\ run\ integration --echo --timing
travis_result $?
travis_cmd codecept\ run\ functional --echo --timing
travis_result $?
travis_cmd codecept\ run\ acceptance --echo --timing
travis_result $?
travis_cmd sudo\ tail\ -75\ \$WP_ROOT_FOLDER/wp-content/debug.log --echo --timing
travis_result $?
travis_cmd sudo\ tail\ -75\ /var/log/php7.1-fpm.log --echo --timing
travis_result $?
travis_cmd sudo\ tail\ -75\ /var/log/fpm-php.www.log --echo --timing
travis_result $?
:
}

EOFUNC_SCRIPT
cat <<'EOFUNC_BEFORE_CACHE' >>$HOME/.travis/job_stages
function travis_run_before_cache() {
:
}

EOFUNC_BEFORE_CACHE
cat <<'EOFUNC_CACHE' >>$HOME/.travis/job_stages
function travis_run_cache() {

travis_fold start cache.2
  echo -e "store build cache"
  if [[ $- = *e* ]]; then
    ERREXIT_SET=true
  fi
  set +e
  if [[ -n $ERREXIT_SET ]]; then
    set -e
  fi
  if [[ $- = *e* ]]; then
    ERREXIT_SET=true
  fi
  set +e
  if [[ -f $CASHER_DIR/bin/casher ]]; then
    travis_cmd type\ rvm\ \&\>/dev/null\ \|\|\ source\ \~/.rvm/scripts/rvm --timing
    travis_cmd rvm\ \$\(travis_internal_ruby\)\ --fuzzy\ do\ \$CASHER_DIR/bin/casher\ push\ https://s3.amazonaws.com/cache_bucket/1234567890//cache-846c7809fe1268c8e960233525d99e9358c17c5d2ab20dc9d3c21354057243f2--php-7.1.tgz\\\?X-Amz-Algorithm\\\=AWS4-HMAC-SHA256\\\&X-Amz-Credential\\\=abcdef0123456789\\\%2F20171221\\\%2Fus-east-1\\\%2Fs3\\\%2Faws4_request\\\&X-Amz-Date\\\=20171221T095914Z\\\&X-Amz-Expires\\\=60\\\&X-Amz-Signature\\\=90999929bbf76201ec53d917fe561f1d1623d62eba82fef6a049ae1fba4e9bef\\\&X-Amz-SignedHeaders\\\=host --timing
  fi
  if [[ -n $ERREXIT_SET ]]; then
    set -e
  fi
travis_fold end cache.2

:
}

EOFUNC_CACHE
cat <<'EOFUNC_RESET_STATE' >>$HOME/.travis/job_stages
function travis_run_reset_state() {
:
}

EOFUNC_RESET_STATE
cat <<'EOFUNC_AFTER_SUCCESS' >>$HOME/.travis/job_stages
function travis_run_after_success() {
:
}

EOFUNC_AFTER_SUCCESS
cat <<'EOFUNC_AFTER_FAILURE' >>$HOME/.travis/job_stages
function travis_run_after_failure() {
:
}

EOFUNC_AFTER_FAILURE
cat <<'EOFUNC_AFTER_SCRIPT' >>$HOME/.travis/job_stages
function travis_run_after_script() {
:
}

EOFUNC_AFTER_SCRIPT
cat <<'EOFUNC_FINISH' >>$HOME/.travis/job_stages
function travis_run_finish() {
:
}

EOFUNC_FINISH
# END_FUNCS
source $HOME/.travis/job_stages
travis_run_setup_filter
travis_run_configure
travis_run_checkout
travis_run_prepare
travis_run_disable_sudo
travis_run_export
travis_run_setup
travis_run_setup_casher
travis_run_setup_cache
travis_run_announce
travis_run_debug
travis_run_before_install
travis_run_install
travis_run_before_script
travis_run_script
travis_run_before_cache
travis_run_cache
travis_run_after_success
travis_run_after_failure
travis_run_after_script
travis_run_finish
echo -e "\nDone. Your build exited with $TRAVIS_TEST_RESULT."

travis_terminate $TRAVIS_TEST_RESULT
