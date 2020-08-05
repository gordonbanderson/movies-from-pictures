FROM php:7.4-cli-buster

RUN apt -y update && apt -y upgrade
RUN apt -y install melt figlet git zip unzip python3-pil python3-pip imagemagick mencoder zlib1g-dev libjpeg-dev \
    imagemagick rename libmagickwand-dev xvfb

#RUN apt-get update && apt-get install -y \
#    libmagickwand-dev --no-install-recommends \
#    && pecl install imagick \
#	&& docker-php-ext-enable imagick

RUN pecl install imagick && docker-php-ext-enable imagick

# Install perceptive hasher in /usr/local/bin
RUN git clone https://github.com/commonsmachinery/blockhash-python.git && \
    cd blockhash-python && python3 setup.py install && cd .. && rm -rf blockhash-python

RUN pip3 install imgp

# alter bash prompt
ENV PS1A="\u@moviesfrompictures:\w> "
RUN echo 'PS1=$PS1A' >> ~/.bashrc

# intro message when attaching to shell
RUN echo 'figlet -w 120 Movies From Pictures' >> ~/.bashrc

# install composer - see https://medium.com/@c.harrison/speedy-composer-installs-in-docker-builds-41eea6d0172b
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && \
    composer global require hirak/prestissimo --no-plugins --no-scripts


# Cleanup
RUN apt-get -y autoremove && apt-get -y clean

# Prevent the container from exiting
CMD tail -f /dev/null

