pipeline {
    agent any

    tools {
        maven 'MAVEN_HOME'
    }

    stages {
        stage('Welcome Stage') {
            steps {
                echo 'Welcome to Pipeline'
            }
        }

        stage('Clean Stage') {
            steps {
                sh 'mvn clean'
            }
        }

        stage('Test Stage') {
            steps {
                sh 'mvn test'
            }
        }

        stage('Build Stage') {
            steps {
                sh 'mvn install'
            }
        }

        stage('Build Success') {
            steps {
                echo 'Build Success'
            }
        }

        stage('Final Success') {
            steps {
                echo 'Final Success'
            }
        }

        stage('Supar Final Success') {
            steps {
                echo 'Supar Final Success'
            }
        }

        stage('Supar dupper Final Success') {
            steps {
                echo 'Supar dupper Final Success 🚀'
            }
        }
    }

    post {
        success {
            echo '✅ Pipeline completed successfully!'
        }
        failure {
            echo '❌ Pipeline failed!'
        }
    }
}
