pipeline {
    agent any

    options {
        timestamps()
    }

    environment {
        IMAGE_TAG = "${env.BUILD_NUMBER}"
    }

    stages {
        stage('Dépendances') {
            steps {
                sh 'composer install --no-interaction --prefer-dist'
            }
        }

        stage('Tests') {
            steps {
                sh 'vendor/bin/phpunit'
            }
        }

        stage('Build des images') {
            steps {
                sh '''
                    docker build --target runtime -t clean-architecture-api:${IMAGE_TAG} .
                    docker build --target web     -t clean-architecture-web:${IMAGE_TAG} .
                '''
            }
        }
    }

    post {
        success { echo "Build ${env.BUILD_NUMBER} OK" }
        failure { echo "Build ${env.BUILD_NUMBER} en échec" }
    }
}
