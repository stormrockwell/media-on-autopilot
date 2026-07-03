import { useSettingsContext } from '../state/SettingsContext';

export function Toggle( { name, label, desc } ) {
	const { values, setField } = useSettingsContext();
	return (
		<>
			<label className="moap-toggle" htmlFor={ name }>
				<input
					id={ name }
					type="checkbox"
					name={ name }
					value="1"
					checked={ values[ name ] === '1' }
					onChange={ ( e ) =>
						setField( name, e.target.checked ? '1' : '' )
					}
				/>
				{ label && (
					<span className="moap-toggle__label">{ label }</span>
				) }
			</label>
			{ desc && <p className="moap-desc">{ desc }</p> }
		</>
	);
}

export function NumberField( { name, min, max } ) {
	const { values, setField } = useSettingsContext();
	return (
		<input
			type="number"
			min={ min }
			max={ max }
			name={ name }
			className="small-text"
			value={ values[ name ] ?? '' }
			onChange={ ( e ) => setField( name, e.target.value ) }
		/>
	);
}

export function Select( { name, options } ) {
	const { values, setField } = useSettingsContext();
	return (
		<select
			name={ name }
			value={ values[ name ] ?? '' }
			onChange={ ( e ) => setField( name, e.target.value ) }
		>
			{ options.map( ( [ value, label ] ) => (
				<option key={ value } value={ value }>
					{ label }
				</option>
			) ) }
		</select>
	);
}

export function TextField( { name, placeholder, className = 'regular-text' } ) {
	const { values, setField } = useSettingsContext();
	return (
		<input
			type="text"
			name={ name }
			className={ className }
			placeholder={ placeholder }
			value={ values[ name ] ?? '' }
			onChange={ ( e ) => setField( name, e.target.value ) }
		/>
	);
}

export function PasswordField( { name, placeholder } ) {
	const { values, setField, secretsSet } = useSettingsContext();
	// The stored secret is never sent to the browser; an empty field while a
	// value is saved means "leave the stored secret unchanged".
	const isSaved = !! ( secretsSet && secretsSet[ name ] );
	return (
		<input
			type="password"
			name={ name }
			className="regular-text"
			autoComplete="off"
			placeholder={ isSaved ? '••••••••' : placeholder }
			value={ values[ name ] ?? '' }
			onChange={ ( e ) => setField( name, e.target.value ) }
		/>
	);
}
