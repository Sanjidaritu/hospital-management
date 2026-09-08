pipeline {
    agent any

    triggers {
        pollSCM('H/5 * * * *')
    }

    stages {

        stage('Deploy to DEV') {
            steps {
                withCredentials([
                    string(
                        credentialsId: 'railway-dev-token',
                        variable: 'RAILWAY_TOKEN'
                    )
                ]) {
                     bat 'railway up --service hospital-management --environment Dev --ci'
                }
            }
        }

        stage('Smoke Test in Dev Env') {
            steps {
                dir('UprightInsurance') {
                    bat 'mvn clean test'
                }
            }
        }
    }

    post {
        always {

            junit(
                testResults: 'UprightInsurance/target/surefire-reports/TEST-*.xml',
                allowEmptyResults: true
            )

            archiveArtifacts(
                artifacts: 'UprightInsurance/target/surefire-reports/**',
                allowEmptyArchive: true
            )

            emailext(
                to: 'uprighttechsolutions@gmail.com',
                subject: "Hospital UI Tests: ${env.JOB_NAME} #${env.BUILD_NUMBER} - ${currentBuild.currentResult}",
                mimeType: 'text/html',
                attachLog: true,
                attachmentsPattern: 'UprightInsurance/target/surefire-reports/emailable-report.html',
                body: """
                    <html>
                        <body>
                            <h2>Hospital UI Automation Results</h2>

                            <p>
                                <b>Status:</b>
                                ${currentBuild.currentResult}
                            </p>

                            <p>
                                <b>Job:</b>
                                ${env.JOB_NAME}
                            </p>

                            <p>
                                <b>Build Number:</b>
                                #${env.BUILD_NUMBER}
                            </p>

                            <p>
                                <b>Test Report:</b>
                                The TestNG HTML report is attached.
                            </p>

                            <p>
                                <b>Console Output:</b>
                                The Jenkins console log is attached.
                            </p>

                            <p>
                                <a href="${env.BUILD_URL}">
                                    Open Jenkins Build
                                </a>
                            </p>
                        </body>
                    </html>
                """
            )
        }

        success {
            echo 'UI automation tests passed.'
        }

        failure {
            echo 'UI automation tests failed. Check the email and console output.'
        }
    }
}