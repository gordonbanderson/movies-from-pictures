FROM php:7.4-cli-buster

ENV DEBIAN_FRONTEND noninteractive

RUN apt -y update && apt -y upgrade
RUN apt -y install melt figlet git zip unzip python3-pil python3-pip imagemagick mencoder zlib1g-dev libjpeg-dev \
    imagemagick rename libmagickwand-dev xvfb wget

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

RUN apt-get -y install locate

# Cleanup
RUN apt-get -y autoremove && apt-get -y clean

USER root

# Add a non-root user to prevent files being created with root permissions on host machine.
ARG PUID=1000
ENV PUID ${PUID}
ARG PGID=1000
ENV PGID ${PGID}

RUN groupadd -g ${PGID} mm && \
        useradd -u ${PUID} -g mm -m mm && \
        usermod -p "*" mm -s /bin/bash

# Download some fonts
# NOT WORKING - SUSPECT MOUNTING ISSUE
#RUN mkdir /var/www/fonts
#RUN wget 'https://dl.dafont.com/dl/?f=roboto' -O /var/www/fonts/Roboto.zip
#RUN ls -lh /var/www/fonts
#RUN unzip /var/www/fonts/Roboto.zip
#RUN mv /*.ttf /var/www/fonts/
#RUN ls -lh /var/www/fonts

#RUN chown -R mm:mm /var/www/fonts


# Prevent the container from exiting
CMD tail -f /dev/null

