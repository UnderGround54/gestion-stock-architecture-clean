pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
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

        stage('Deploy staging (VM)') {
            when {
                expression { env.GIT_BRANCH == 'origin/dev' }
            }
            environment {
                STAGING_HOST = '10.84.104.230'
            }
            steps {
                withCredentials([
                    sshUserPrivateKey(credentialsId: 'staging-ssh',
                                      keyFileVariable: 'SSH_KEY',
                                      usernameVariable: 'SSH_USER'),
                    string(credentialsId: 'staging-db-password', variable: 'DB_PASSWORD'),
                    string(credentialsId: 'staging-app-secret',  variable: 'APP_SECRET')
                ]) {
                    sh '''
                        set -e
                        SSH_OPTS="-i $SSH_KEY -o StrictHostKeyChecking=accept-new -o BatchMode=yes"
                        TARGET="$SSH_USER@$STAGING_HOST"

                        # 1. Dossier de l'application + fichier compose
                        ssh $SSH_OPTS $TARGET "mkdir -p app"
                        scp $SSH_OPTS /src/devops-lab/staging/docker-compose.yml $TARGET:app/docker-compose.yml

                        # 2. Variables d'environnement (lisibles uniquement par l'utilisateur)
                        printf 'IMAGE_TAG=%s\\nDB_PASSWORD=%s\\nAPP_SECRET=%s\\n' \
                            "$IMAGE_TAG" "$DB_PASSWORD" "$APP_SECRET" \
                          | ssh $SSH_OPTS $TARGET 'umask 077; cat > app/.env'

                        # 3. Transfert des images vers la VM
                        docker save clean-architecture-api:$IMAGE_TAG clean-architecture-web:$IMAGE_TAG \
                          | ssh $SSH_OPTS $TARGET 'docker load'

                        # 4. Déploiement
                        ssh $SSH_OPTS $TARGET 'cd app && docker compose -p staging up -d --remove-orphans'
                    '''
                }
            }
        }
    }

    post {
        success { echo "Build ${env.BUILD_NUMBER} OK" }
        failure { echo "Build ${env.BUILD_NUMBER} en échec" }
    }
}
