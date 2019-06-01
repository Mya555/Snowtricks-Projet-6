# Snowtricks-Projet-6

<p>The projet is created for the curcus of OpenClassrooms.</p>
<p>The project must be created about the snow trick community , and without any Bundle.</p>
<p>Developped with the Symfony 4 framework.</p>

# The quality of the code

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/4c8d2721113a40f386ca0b7cbb3de67c)](https://app.codacy.com/app/Mya555/Snowtricks-Projet-6?utm_source=github.com&utm_medium=referral&utm_content=Mya555/Snowtricks-Projet-6&utm_campaign=Badge_Grade_Dashboard)

# How to install the project

<h4>1 - Download or clone the repository git</h4>
<pre><code>https://github.com/Mya555/Snowtricks-Projet-6.git</pre></code>

<h4>2 - Download dependencies :</h4>
<pre><code>composer install</pre></code> 

<h4>3 - Create database :</h4>
<pre><code>php bin/console doctrine:database:create</pre></code>

<h4>4 - Create schema :</h4>
<pre><code>php bin/console doctrine:schema:update --force</pre></code>

<h4>5 - Load fixtures :</h4>
<pre><code>php bin/console doctrine:fixtures:load</pre></code>

<h4>6 - Run the server :</h4>
<pre><code>PHP -S localhost:8080</pre></code>

# Existing users
<table>
<thead>
  <tr>
  <th>login</th>
  <th>password</th>
  </tr>
</thead>
  <tbody>
<tr>
<td>jane</td>
<td>123456</td>
</tr>
<tr>
<td>jone</td>
<td>123456</td>
</tr>
</tbody>
</table>




